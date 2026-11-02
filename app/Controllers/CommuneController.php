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

/**
 * Gestion des communes.
 */
final class CommuneController extends Controller
{
    /** Nombre maximum de communes créées en une seule fois. */
    private const MAX_MULTI = 50;

    public function index(Request $request): void
    {
        $page      = max(1, $request->int('page', 1));
        $perPage   = (int) Config::get('pagination.per_page', 10);
        $search    = $request->string('q');
        $departementId = $request->int('departement');

        $paginator = Commune::paginate($search, $departementId, $page, $perPage);

        $this->view('communes/index', [
            'pageTitle'     => 'Communes',
            'communes'      => $paginator,
            'search'        => $search,
            'departementId' => $departementId,
            'departements'  => Departement::options(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('communes/form', [
            'pageTitle'    => 'Nouvelle commune',
            'commune'      => null,
            'departements' => Departement::options(),
            'formAction'   => url('communes'),
            'mode'         => 'create',
        ]);
    }

    public function createMultiple(Request $request): void
    {
        $nombre = $request->int('nombre');

        if ($nombre < 2 || $nombre > self::MAX_MULTI) {
            $nombre = 0;
        }

        $oldItems = old('communes', []);
        $oldItems = is_array($oldItems) ? $oldItems : [];

        $startStep = $nombre > 0
            ? max(0, min($nombre - 1, $request->int('etape', 0)))
            : 0;

        $this->view('communes/form_multi', [
            'pageTitle'    => 'Saisie multiple de communes',
            'nombre'       => $nombre,
            'departements' => Departement::options(),
            'oldItems'     => $oldItems,
            'startStep'    => $startStep,
        ]);
    }

    public function store(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'nom'           => 'required|minlength:2|maxlength:250',
            'iddepartement' => 'required|integer|exists:departement,iddepartement',
        ]);

        if ($validator->fails()) {
            $this->fail('communes/creer', (string) $validator->firstError(), $request->all());
        }

        $nom    = $request->string('nom');
        $depId  = $request->int('iddepartement');

        if (Commune::nameExistsInDepartement($nom, $depId)) {
            $this->fail('communes/creer', 'Une commune portant ce nom existe déjà dans ce département.', $request->all());
        }

        Commune::create(['nom' => $nom, 'iddepartement' => $depId]);

        $this->succeed('communes', 'La commune « ' . $nom . ' » a été ajoutée.');
    }

    public function storeMultiple(Request $request): void
    {
        $items = $request->input('communes');

        if (!is_array($items) || $items === []) {
            $this->fail('communes/creer-multiple', 'Aucune commune à enregistrer.', [], ['nombre' => 2]);
        }

        $nombre = count($items);

        if ($nombre > self::MAX_MULTI) {
            $this->fail(
                'communes/creer-multiple',
                'Vous ne pouvez pas enregistrer plus de ' . self::MAX_MULTI . ' communes à la fois.',
                [],
                ['nombre' => self::MAX_MULTI]
            );
        }

        $departements = [];
        foreach (Departement::options() as $departement) {
            $departements[(int) $departement['iddepartement']] = (string) $departement['nom'];
        }

        $seen       = [];
        $clean      = [];
        $firstError = null;
        $errorStep  = 0;

        foreach (array_values($items) as $index => $item) {
            $nom   = is_array($item) ? trim((string) ($item['nom'] ?? '')) : '';
            $depId = is_array($item) ? (int) ($item['iddepartement'] ?? 0) : 0;

            $error = null;

            if (mb_strlen($nom) < 2 || mb_strlen($nom) > 250) {
                $error = 'Le nom de la commune #' . ($index + 1) . ' est invalide (2 à 250 caractères).';
            } elseif (!isset($departements[$depId])) {
                $error = 'Sélectionnez un département pour la commune #' . ($index + 1) . '.';
            } else {
                $key = $depId . '|' . mb_strtolower($nom);

                if (isset($seen[$key])) {
                    $error = 'La commune « ' . $nom . ' » est en double dans la liste.';
                } elseif (Commune::nameExistsInDepartement($nom, $depId)) {
                    $error = 'La commune « ' . $nom . ' » existe déjà dans le département '
                        . $departements[$depId] . '.';
                } else {
                    $seen[$key] = true;
                }
            }

            if ($error !== null && $firstError === null) {
                $firstError = $error;
                $errorStep  = $index;
            }

            if ($error === null) {
                $clean[] = ['nom' => $nom, 'iddepartement' => $depId];
            }
        }

        if ($firstError !== null) {
            $this->fail(
                'communes/creer-multiple',
                $firstError,
                ['communes' => $items],
                ['nombre' => $nombre, 'etape' => $errorStep]
            );
        }

        Commune::createMany($clean);

        $message = $nombre === 1
            ? '1 commune a été ajoutée.'
            : $nombre . ' communes ont été ajoutées.';

        $this->succeed('communes', $message);
    }

    public function edit(Request $request, string $id): void
    {
        $commune = Commune::findDetailed((int) $id);

        if ($commune === null) {
            throw HttpException::notFound('Cette commune est introuvable.');
        }

        $this->view('communes/form', [
            'pageTitle'    => 'Modifier « ' . $commune['nom'] . ' »',
            'commune'      => $commune,
            'departements' => Departement::options(),
            'formAction'   => url('communes/' . (int) $commune['idcommune']),
            'mode'         => 'edit',
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $communeId = (int) $id;
        $commune   = Commune::find($communeId);

        if ($commune === null) {
            throw HttpException::notFound('Cette commune est introuvable.');
        }

        $validator = Validator::make($request->all(), [
            'nom'           => 'required|minlength:2|maxlength:250',
            'iddepartement' => 'required|integer|exists:departement,iddepartement',
        ]);

        if ($validator->fails()) {
            $this->fail('communes/' . $communeId . '/editer', (string) $validator->firstError(), $request->all());
        }

        $nom   = $request->string('nom');
        $depId = $request->int('iddepartement');

        if (Commune::nameExistsInDepartement($nom, $depId, $communeId)) {
            $this->fail('communes/' . $communeId . '/editer', 'Une commune portant ce nom existe déjà dans ce département.', $request->all());
        }

        Commune::updateById($communeId, ['nom' => $nom, 'iddepartement' => $depId]);

        $this->succeed('communes', 'La commune « ' . $nom . ' » a été mise à jour.');
    }

    public function destroy(Request $request, string $id): void
    {
        $communeId = (int) $id;
        $commune   = Commune::find($communeId);

        if ($commune === null) {
            throw HttpException::notFound('Cette commune est introuvable.');
        }

        if (Commune::hasVilles($communeId)) {
            $this->fail('communes', 'Impossible de supprimer « ' . $commune['nom'] . ' » : des villes y sont rattachées.');
        }

        if (Arrondissement::countForCommune($communeId) > 0) {
            $this->fail('communes', 'Impossible de supprimer « ' . $commune['nom'] . ' » : des arrondissements y sont rattachés.');
        }

        Commune::deleteById($communeId);

        $this->succeed('communes', 'La commune « ' . $commune['nom'] . ' » a été supprimée.');
    }
}