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
use App\Models\Departement;

/**
 * Gestion des départements.
 */
final class DepartementController extends Controller
{
    public function index(Request $request): void
    {
        $page    = max(1, $request->int('page', 1));
        $perPage = (int) Config::get('pagination.per_page', 10);
        $search  = $request->string('q');

        $paginator = Departement::paginate($search, $page, $perPage);

        $this->view('departements/index', [
            'pageTitle'    => 'Départements',
            'departements' => $paginator,
            'search'       => $search,
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('departements/form', [
            'pageTitle'  => 'Nouveau département',
            'departement'=> null,
            'formAction' => url('departements'),
            'mode'       => 'create',
        ]);
    }

    public function store(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|minlength:2|maxlength:255|unique:departement,nom',
        ]);

        if ($validator->fails()) {
            $this->fail('departements/creer', (string) $validator->firstError(), $request->all());
        }

        Departement::create(['nom' => $request->string('nom')]);

        $this->succeed('departements', 'Le département « ' . $request->string('nom') . ' » a été ajouté.');
    }

    public function edit(Request $request, string $id): void
    {
        $departement = Departement::find((int) $id);

        if ($departement === null) {
            throw HttpException::notFound('Ce département est introuvable.');
        }

        $this->view('departements/form', [
            'pageTitle'   => 'Modifier « ' . $departement['nom'] . ' »',
            'departement' => $departement,
            'formAction'  => url('departements/' . (int) $departement['iddepartement']),
            'mode'        => 'edit',
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $departementId = (int) $id;
        $departement   = Departement::find($departementId);

        if ($departement === null) {
            throw HttpException::notFound('Ce département est introuvable.');
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|minlength:2|maxlength:255|unique:departement,nom,'
                . $departementId . ',iddepartement',
        ]);

        if ($validator->fails()) {
            $this->fail('departements/' . $departementId . '/editer', (string) $validator->firstError(), $request->all());
        }

        Departement::updateById($departementId, ['nom' => $request->string('nom')]);

        $this->succeed('departements', 'Le département « ' . $request->string('nom') . ' » a été mis à jour.');
    }

    public function destroy(Request $request, string $id): void
    {
        $departementId = (int) $id;
        $departement   = Departement::find($departementId);

        if ($departement === null) {
            throw HttpException::notFound('Ce département est introuvable.');
        }

        if (Departement::hasCommunes($departementId)) {
            $this->fail('departements', 'Impossible de supprimer « ' . $departement['nom'] . ' » : des communes y sont rattachées.');
        }

        Departement::deleteById($departementId);

        $this->succeed('departements', 'Le département « ' . $departement['nom'] . ' » a été supprimé.');
    }
}