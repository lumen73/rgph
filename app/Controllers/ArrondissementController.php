<?php
declare(strict_types=1);

namespace App\Controllers;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Core\Config;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Exceptions\HttpException;
use App\Models\Arrondissement;
use App\Models\Commune;
use App\Models\Departement;
use App\Models\Ville;

/**
 * Gestion des arrondissements (liste, création simple ou multiple,
 * modification, suppression).
 */
final class ArrondissementController extends Controller
{
    /** Nombre maximum d'arrondissements créés en une seule fois. */
    private const MAX_MULTI = 50;

    public function index(Request $request): void
    {
        $page          = max(1, $request->int('page', 1));
        $perPage       = (int) Config::get('pagination.per_page', 10);
        $search        = $request->string('q');
        $departementId = $request->int('departement');
        $communeId     = $request->int('commune');

        $paginator = Arrondissement::paginate($search, $communeId, $departementId, $page, $perPage);

        $this->view('arrondissements/index', [
            'pageTitle'     => 'Arrondissements',
            'arrondissements' => $paginator,
            'search'        => $search,
            'departementId' => $departementId,
            'communeId'     => $communeId,
            'departements'  => Departement::options(),
            'communes'      => Commune::options($departementId),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('arrondissements/form', [
            'pageTitle'      => 'Nouvel arrondissement',
            'arrondissement' => null,
            'communes'       => Commune::options(),
            'formAction'     => url('arrondissements'),
            'mode'           => 'create',
        ]);
    }

    public function createMultiple(Request $request): void
    {
        $nombre = $request->int('nombre');

        if ($nombre < 2 || $nombre > self::MAX_MULTI) {
            $nombre = 0;
        }

        $oldItems = old('arrondissements', []);
        $oldItems = is_array($oldItems) ? $oldItems : [];

        $startStep = $nombre > 0
            ? max(0, min($nombre - 1, $request->int('etape', 0)))
            : 0;

        $this->view('arrondissements/form_multi', [
            'pageTitle'    => 'Saisie multiple d\'arrondissements',
            'nombre'       => $nombre,
            'communes'     => Commune::options(),
            'oldItems'     => $oldItems,
            'startStep'    => $startStep,
        ]);
    }

    public function store(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'nom'       => 'required|minlength:2|maxlength:250',
            'idcommune' => 'required|integer|exists:commune,idcommune',
        ]);

        if ($validator->fails()) {
            $this->fail('arrondissements/creer', (string) $validator->firstError(), $request->all());
        }

        $nom       = $request->string('nom');
        $communeId = $request->int('idcommune');

        if (Arrondissement::nameExistsInCommune($nom, $communeId)) {
            $this->fail(
                'arrondissements/creer',
                'Un arrondissement portant ce nom existe déjà dans cette commune.',
                $request->all()
            );
        }

        Arrondissement::create(['nom' => $nom, 'idcommune' => $communeId]);

        $this->succeed('arrondissements', 'L\'arrondissement « ' . $nom . ' » a été ajouté.');
    }

    public function storeMultiple(Request $request): void
    {
        $items = $request->input('arrondissements');

        if (!is_array($items) || $items === []) {
            $this->fail('arrondissements/creer-multiple', 'Aucun arrondissement à enregistrer.', [], ['nombre' => 2]);
        }

        $nombre = count($items);

        if ($nombre > self::MAX_MULTI) {
            $this->fail(
                'arrondissements/creer-multiple',
                'Vous ne pouvez pas enregistrer plus de ' . self::MAX_MULTI . ' arrondissements à la fois.',
                [],
                ['nombre' => self::MAX_MULTI]
            );
        }

        $communes = [];
        foreach (Commune::options() as $commune) {
            $communes[(int) $commune['idcommune']] = (string) $commune['nom'];
        }

        $seen       = [];
        $clean      = [];
        $firstError = null;
        $errorStep  = 0;

        foreach (array_values($items) as $index => $item) {
            $nom       = is_array($item) ? trim((string) ($item['nom'] ?? '')) : '';
            $communeId = is_array($item) ? (int) ($item['idcommune'] ?? 0) : 0;

            $error = null;

            if (mb_strlen($nom) < 2 || mb_strlen($nom) > 250) {
                $error = 'Le nom de l\'arrondissement #' . ($index + 1) . ' est invalide (2 à 250 caractères).';
            } elseif (!isset($communes[$communeId])) {
                $error = 'Sélectionnez une commune pour l\'arrondissement #' . ($index + 1) . '.';
            } else {
                $key = $communeId . '|' . mb_strtolower($nom);

                if (isset($seen[$key])) {
                    $error = 'L\'arrondissement « ' . $nom . ' » est en double dans la liste.';
                } elseif (Arrondissement::nameExistsInCommune($nom, $communeId)) {
                    $error = 'L\'arrondissement « ' . $nom . ' » existe déjà dans la commune '
                        . $communes[$communeId] . '.';
                } else {
                    $seen[$key] = true;
                }
            }

            if ($error !== null && $firstError === null) {
                $firstError = $error;
                $errorStep  = $index;
            }

            if ($error === null) {
                $clean[] = ['nom' => $nom, 'idcommune' => $communeId];
            }
        }

        if ($firstError !== null) {
            $this->fail(
                'arrondissements/creer-multiple',
                $firstError,
                ['arrondissements' => $items],
                ['nombre' => $nombre, 'etape' => $errorStep]
            );
        }

        Arrondissement::createMany($clean);

        $message = $nombre === 1
            ? '1 arrondissement a été ajouté.'
            : $nombre . ' arrondissements ont été ajoutés.';

        $this->succeed('arrondissements', $message);
    }

    public function edit(Request $request, string $id): void
    {
        $arrondissement = Arrondissement::findDetailed((int) $id);

        if ($arrondissement === null) {
            throw HttpException::notFound('Cet arrondissement est introuvable.');
        }

        $this->view('arrondissements/form', [
            'pageTitle'      => 'Modifier « ' . $arrondissement['nom'] . ' »',
            'arrondissement' => $arrondissement,
            'communes'       => Commune::options(),
            'formAction'     => url('arrondissements/' . (int) $arrondissement['idarrondissement']),
            'mode'           => 'edit',
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $arrondissementId = (int) $id;
        $arrondissement   = Arrondissement::find($arrondissementId);

        if ($arrondissement === null) {
            throw HttpException::notFound('Cet arrondissement est introuvable.');
        }

        $validator = Validator::make($request->all(), [
            'nom'       => 'required|minlength:2|maxlength:250',
            'idcommune' => 'required|integer|exists:commune,idcommune',
        ]);

        if ($validator->fails()) {
            $this->fail(
                'arrondissements/' . $arrondissementId . '/editer',
                (string) $validator->firstError(),
                $request->all()
            );
        }

        $nom       = $request->string('nom');
        $communeId = $request->int('idcommune');

        if (Arrondissement::nameExistsInCommune($nom, $communeId, $arrondissementId)) {
            $this->fail(
                'arrondissements/' . $arrondissementId . '/editer',
                'Un arrondissement portant ce nom existe déjà dans cette commune.',
                $request->all()
            );
        }

        Arrondissement::updateById($arrondissementId, ['nom' => $nom, 'idcommune' => $communeId]);

        $this->succeed('arrondissements', 'L\'arrondissement « ' . $nom . ' » a été mis à jour.');
    }

    public function destroy(Request $request, string $id): void
    {
        $arrondissementId = (int) $id;
        $arrondissement   = Arrondissement::find($arrondissementId);

        if ($arrondissement === null) {
            throw HttpException::notFound('Cet arrondissement est introuvable.');
        }

        $linkedVilles = Ville::countWhere('idarrondissement = :id', ['id' => $arrondissementId]);

        if ($linkedVilles > 0) {
            $this->fail(
                'arrondissements',
                'Suppression impossible : ' . $linkedVilles . ' ville(s) sont rattachées à l\'arrondissement « '
                . $arrondissement['nom'] . ' ».'
            );
        }

        Arrondissement::deleteById($arrondissementId);

        $this->succeed('arrondissements', 'L\'arrondissement « ' . $arrondissement['nom'] . ' » a été supprimé.');
    }
}
