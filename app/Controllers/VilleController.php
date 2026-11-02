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
 * Gestion des villes (liste, création simple ou multiple, consultation,
 * édition, suppression).
 */
final class VilleController extends Controller
{
    /** Nombre maximum de villes créées en une seule fois. */
    private const MAX_MULTI = 50;

    public function index(Request $request): void
    {
        $page      = max(1, $request->int('page', 1));
        $perPage   = (int) Config::get('pagination.per_page', 10);
        $search    = $request->string('q');
        $departementId = $request->int('departement');
        $communeId = $request->int('commune');
        $sort      = $request->string('tri', 'population');
        $direction = $request->string('ordre', 'desc');

        $paginator = Ville::paginate(
            $search,
            $departementId,
            $communeId,
            $sort,
            $direction,
            $page,
            $perPage
        );

        $this->view('villes/index', [
            'pageTitle'     => 'Villes',
            'villes'        => $paginator,
            'search'        => $search,
            'departementId' => $departementId,
            'communeId'     => $communeId,
            'sort'          => $sort,
            'direction'     => $direction,
            'departements'  => Departement::options(),
            'communes'      => Commune::options($departementId),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('villes/form', [
            'pageTitle'      => 'Nouvelle ville',
            'ville'          => null,
            'arrondissements' => Arrondissement::optionsWithLocation(),
            'communes'       => Commune::options(),
            'formAction'     => url('villes'),
            'mode'           => 'create',
        ]);
    }

    public function createMultiple(Request $request): void
    {
        $nombre = $request->int('nombre');

        if ($nombre < 2 || $nombre > self::MAX_MULTI) {
            $nombre = 0;
        }

        $oldItems = old('villes', []);
        $oldItems = is_array($oldItems) ? $oldItems : [];

        $startStep = $nombre > 0
            ? max(0, min($nombre - 1, $request->int('etape', 0)))
            : 0;

        $this->view('villes/form_multi', [
            'pageTitle'      => 'Saisie multiple de villes',
            'nombre'         => $nombre,
            'arrondissements' => Arrondissement::optionsWithLocation(),
            'communes'       => Commune::options(),
            'oldItems'       => $oldItems,
            'startStep'      => $startStep,
        ]);
    }

    public function store(Request $request): void
    {
        $data = $this->validatedPayload($request, 'villes/creer');

        if (Ville::nameExistsInCommune($data['nom'], (int) $data['idcommune'])) {
            $this->fail(
                'villes/creer',
                'Une ville portant ce nom existe déjà dans cette commune.',
                $request->all()
            );
        }

        Ville::create($data);

        $this->succeed('villes', 'La ville « ' . $data['nom'] . ' » a été ajoutée.');
    }

    public function storeMultiple(Request $request): void
    {
        $items = $request->input('villes');

        if (!is_array($items) || $items === []) {
            $this->fail('villes/creer-multiple', 'Aucune ville à enregistrer.', [], ['nombre' => 2]);
        }

        $nombre = count($items);

        if ($nombre > self::MAX_MULTI) {
            $this->fail(
                'villes/creer-multiple',
                'Vous ne pouvez pas enregistrer plus de ' . self::MAX_MULTI . ' villes à la fois.',
                [],
                ['nombre' => self::MAX_MULTI]
            );
        }

        $arrondissements = [];
        foreach (Arrondissement::optionsWithLocation() as $arrondissement) {
            $arrondissements[(int) $arrondissement['idarrondissement']] = $arrondissement;
        }

        $communes = [];
        foreach (Commune::options() as $commune) {
            $communes[(int) $commune['idcommune']] = $commune;
        }

        $seen       = [];
        $clean      = [];
        $firstError = null;
        $errorStep  = 0;

        foreach (array_values($items) as $index => $item) {
            $nom           = is_array($item) ? trim((string) ($item['nom'] ?? '')) : '';
            $populationRaw = is_array($item) ? ($item['population'] ?? '') : '';
            $arrId         = is_array($item) ? (int) ($item['idarrondissement'] ?? 0) : 0;
            $communeId     = is_array($item) ? (int) ($item['idcommune'] ?? 0) : 0;
            $population    = filter_var($populationRaw, FILTER_VALIDATE_INT);

            $error = null;

            if (mb_strlen($nom) < 2 || mb_strlen($nom) > 250) {
                $error = 'Le nom de la ville #' . ($index + 1) . ' est invalide (2 à 250 caractères).';
            } elseif ($population === false || (int) $population < 0) {
                $error = 'La population de la ville #' . ($index + 1) . ' doit être un entier positif ou nul.';
            } else {
                if ($arrId > 0) {
                    if (!isset($arrondissements[$arrId])) {
                        $error = 'L\'arrondissement sélectionné pour la ville #' . ($index + 1) . ' est introuvable.';
                    } else {
                        $communeId = (int) $arrondissements[$arrId]['idcommune'];
                    }
                }

                if ($error === null) {
                    if ($communeId <= 0 || !isset($communes[$communeId])) {
                        $error = 'Sélectionnez une commune pour la ville #' . ($index + 1) . '.';
                    } else {
                        $key = $communeId . '|' . mb_strtolower($nom);

                        if (isset($seen[$key])) {
                            $error = 'La ville « ' . $nom . ' » est en double dans la liste.';
                        } elseif (Ville::nameExistsInCommune($nom, $communeId)) {
                            $error = 'La ville « ' . $nom . ' » existe déjà dans la commune '
                                . $communes[$communeId]['nom'] . '.';
                        } else {
                            $seen[$key] = true;
                        }
                    }
                }
            }

            if ($error !== null && $firstError === null) {
                $firstError = $error;
                $errorStep  = $index;
            }

            if ($error === null) {
                $clean[] = [
                    'nom'              => $nom,
                    'population'       => (int) $population,
                    'idcommune'        => $communeId,
                    'idarrondissement' => $arrId > 0 ? $arrId : null,
                ];
            }
        }

        if ($firstError !== null) {
            $this->fail(
                'villes/creer-multiple',
                $firstError,
                ['villes' => $items],
                ['nombre' => $nombre, 'etape' => $errorStep]
            );
        }

        Ville::createMany($clean);

        $message = $nombre === 1
            ? '1 ville a été ajoutée.'
            : $nombre . ' villes ont été ajoutées.';

        $this->succeed('villes', $message);
    }

    public function show(Request $request, string $id): void
    {
        $ville = Ville::findDetailed((int) $id);

        if ($ville === null) {
            throw HttpException::notFound('Cette ville est introuvable.');
        }

        $this->view('villes/show', [
            'pageTitle' => $ville['nom'],
            'ville'     => $ville,
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        $ville = Ville::findDetailed((int) $id);

        if ($ville === null) {
            throw HttpException::notFound('Cette ville est introuvable.');
        }

        $this->view('villes/form', [
            'pageTitle'       => 'Modifier « ' . $ville['nom'] . ' »',
            'ville'           => $ville,
            'arrondissements' => Arrondissement::optionsWithLocation(),
            'communes'        => Commune::options(),
            'formAction'      => url('villes/' . (int) $ville['idville']),
            'mode'            => 'edit',
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $villeId  = (int) $id;
        $existing = Ville::find($villeId);

        if ($existing === null) {
            throw HttpException::notFound('Cette ville est introuvable.');
        }

        $data = $this->validatedPayload($request, 'villes/' . $villeId . '/editer');

        if (Ville::nameExistsInCommune($data['nom'], (int) $data['idcommune'], $villeId)) {
            $this->fail(
                'villes/' . $villeId . '/editer',
                'Une ville portant ce nom existe déjà dans cette commune.',
                $request->all()
            );
        }

        $result = Ville::updateWithCapacity($villeId, $data);

        if (!$result['ok']) {
            $this->fail(
                'villes/' . $villeId . '/editer',
                (string) $result['message'],
                $request->all()
            );
        }

        $this->succeed('villes', 'La ville « ' . $data['nom'] . ' » a été mise à jour.');
    }

    public function destroy(Request $request, string $id): void
    {
        $villeId = (int) $id;
        $ville   = Ville::find($villeId);

        if ($ville === null) {
            throw HttpException::notFound('Cette ville est introuvable.');
        }

        $result = Ville::deleteWithCapacity($villeId);

        if (!$result['ok']) {
            $this->fail('villes', (string) $result['message']);
        }

        $this->succeed('villes', 'La ville « ' . $ville['nom'] . ' » a été supprimée.');
    }

    /**
     * Valide et normalise les données d'une ville.
     *
     * La commune est toujours déduite de l'arrondissement lorsqu'il est
     * renseigné : le client ne peut pas créer d'incohérence hiérarchique.
     *
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, string $failPath): array
    {
        $validator = Validator::make($request->all(), [
            'nom'              => 'required|minlength:2|maxlength:250',
            'population'       => 'required|integer|min:0',
            'idcommune'        => 'integer|exists:commune,idcommune',
            'idarrondissement' => 'integer|exists:arrondissement,idarrondissement',
        ]);

        if ($validator->fails()) {
            $this->fail($failPath, (string) $validator->firstError(), $request->all());
        }

        $arrondissementId = $request->int('idarrondissement');
        $communeId        = $request->int('idcommune');

        if ($arrondissementId > 0) {
            $arrondissement = Arrondissement::findDetailed($arrondissementId);

            if ($arrondissement === null) {
                $this->fail($failPath, 'L\'arrondissement sélectionné est introuvable.', $request->all());
            }

            $communeId = (int) $arrondissement['idcommune'];
        }

        if ($communeId <= 0 || Commune::find($communeId) === null) {
            $this->fail(
                $failPath,
                'Sélectionnez une commune ou un arrondissement pour cette ville.',
                $request->all()
            );
        }

        return [
            'nom'              => $request->string('nom'),
            'population'       => $request->int('population'),
            'idcommune'        => $communeId,
            'idarrondissement' => $arrondissementId > 0 ? $arrondissementId : null,
        ];
    }
}
