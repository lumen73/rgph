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
use App\Models\Registre;
use App\Models\Ville;
use DateTime;

/**
 * Gestion du registre des profils individuels (nom, prénom, date de
 * référence, genre) rattachés aux villes.
 */
final class RegistreController extends Controller
{
    public function index(Request $request): void
    {
        $page    = max(1, $request->int('page', 1));
        $perPage = (int) Config::get('pagination.per_page', 10);
        $search  = $request->string('q');
        $villeId = $request->int('ville');
        $profil  = $request->string('profil');

        if (!in_array($profil, ['M', 'F', 'junior'], true)) {
            $profil = '';
        }

        $paginator = Registre::paginate($search, $villeId, $profil, $page, $perPage);

        $this->view('registres/index', [
            'pageTitle'    => 'Registre',
            'registres'    => $paginator,
            'stats'        => Registre::stats(),
            'villes'       => Ville::optionsWithCapacity(),
            'search'       => $search,
            'villeId'      => $villeId,
            'profil'       => $profil,
        ]);
    }

    public function create(Request $request): void
    {
        $selectedVille = (int) old('idville', $request->int('ville'));

        $this->view('registres/form', [
            'pageTitle'    => 'Nouveau profil',
            'registre'     => null,
            'villes'       => Ville::optionsWithCapacity(),
            'selectedVille' => $selectedVille,
            'formAction'   => url('registres'),
            'mode'         => 'create',
        ]);
    }

    public function store(Request $request): void
    {
        $data   = $this->validatedPayload($request, 'registres/creer');
        $result = Registre::createWithinCapacity($data);

        if (!$result['ok']) {
            $this->fail('registres/creer', (string) $result['message'], $request->all());
        }

        $this->succeed(
            'registres',
            'Le profil « ' . $data['prenom'] . ' ' . $data['nom'] . ' » a été ajouté.'
        );
    }

    public function edit(Request $request, string $id): void
    {
        $registre = Registre::findDetailed((int) $id);

        if ($registre === null) {
            throw HttpException::notFound('Ce profil est introuvable.');
        }

        $this->view('registres/form', [
            'pageTitle'     => 'Modifier « ' . $registre['prenom'] . ' ' . $registre['nom'] . ' »',
            'registre'      => $registre,
            'villes'        => Ville::optionsWithCapacity(),
            'selectedVille' => (int) old('idville', $registre['idville']),
            'formAction'    => url('registres/' . (int) $registre['idregistre']),
            'mode'          => 'edit',
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $registreId = (int) $id;
        $existing   = Registre::find($registreId);

        if ($existing === null) {
            throw HttpException::notFound('Ce profil est introuvable.');
        }

        $data   = $this->validatedPayload($request, 'registres/' . $registreId . '/editer');
        $result = Registre::updateWithinCapacity($registreId, $data);

        if (!$result['ok']) {
            $this->fail(
                'registres/' . $registreId . '/editer',
                (string) $result['message'],
                $request->all()
            );
        }

        $this->succeed(
            'registres',
            'Le profil « ' . $data['prenom'] . ' ' . $data['nom'] . ' » a été mis à jour.'
        );
    }

    public function destroy(Request $request, string $id): void
    {
        $registreId = (int) $id;
        $registre   = Registre::find($registreId);

        if ($registre === null) {
            throw HttpException::notFound('Ce profil est introuvable.');
        }

        Registre::deleteById($registreId);

        $this->succeed(
            'registres',
            'Le profil « ' . $registre['prenom'] . ' ' . $registre['nom'] . ' » a été supprimé.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, string $failPath): array
    {
        $validator = Validator::make($request->all(), [
            'nom'     => 'required|minlength:2|maxlength:255',
            'prenom'  => 'required|minlength:2|maxlength:255',
            'date_ref' => 'required',
            'genre'   => 'required|in:M,F',
            'idville' => 'required|integer|exists:ville,idville',
        ]);

        if ($validator->fails()) {
            $this->fail($failPath, (string) $validator->firstError(), $request->all());
        }

        $dateRef = $request->string('date_ref');
        $dateError = $this->dateRefError($dateRef);

        if ($dateError !== null) {
            $this->fail($failPath, $dateError, $request->all());
        }

        return [
            'nom'      => $request->string('nom'),
            'prenom'   => $request->string('prenom'),
            'date_ref' => $dateRef,
            'genre'    => $request->string('genre'),
            'idville'  => $request->int('idville'),
        ];
    }

    private function dateRefError(string $value): ?string
    {
        $parsed = DateTime::createFromFormat('!Y-m-d', $value);

        if ($parsed === false || $parsed->format('Y-m-d') !== $value) {
            return 'La date de référence doit être une date valide au format AAAA-MM-JJ.';
        }

        $today = new DateTime('today');

        if ($parsed > $today) {
            return 'La date de référence ne peut pas être dans le futur.';
        }

        if ($parsed < new DateTime('1900-01-01')) {
            return 'La date de référence doit être postérieure ou égale au 1er janvier 1900.';
        }

        return null;
    }
}
