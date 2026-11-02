<?php
declare(strict_types=1);

namespace App\Controllers;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Exceptions\HttpException;
use App\Models\Utilisateur;

/**
 * Gestion des utilisateurs (réservée aux administrateurs).
 */
final class UtilisateurController extends Controller
{
    /** @var array<int, string> */
    private const ROLES = ['admin', 'agent', 'lecteur'];

    public function index(Request $request): void
    {
        $page    = max(1, $request->int('page', 1));
        $perPage = (int) Config::get('pagination.per_page', 10);
        $search  = $request->string('q');
        $role    = $request->string('role');

        if (!in_array($role, self::ROLES, true)) {
            $role = '';
        }

        $paginator = Utilisateur::paginate($search, $role, $page, $perPage);

        $this->view('utilisateurs/index', [
            'pageTitle'    => 'Utilisateurs',
            'utilisateurs' => $paginator,
            'search'       => $search,
            'role'         => $role,
            'roles'        => self::ROLES,
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('utilisateurs/form', [
            'pageTitle'   => 'Nouvel utilisateur',
            'utilisateur' => null,
            'roles'       => self::ROLES,
            'formAction'  => url('utilisateurs'),
            'mode'        => 'create',
        ]);
    }

    public function store(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'nom'   => 'required|minlength:2|maxlength:250',
            'email' => 'required|email|maxlength:250|unique:utilisateur,email',
            'mdp'   => 'required|minlength:6|maxlength:100',
            'role'  => 'required|in:' . implode(',', self::ROLES),
        ]);

        if ($validator->fails()) {
            $this->fail('utilisateurs/creer', (string) $validator->firstError(), $request->all());
        }

        Utilisateur::create([
            'nom'   => $request->string('nom'),
            'email' => $request->string('email'),
            'role'  => $request->string('role'),
            'mdp'   => password_hash((string) $request->input('mdp', ''), PASSWORD_DEFAULT),
        ]);

        $this->succeed('utilisateurs', 'L\'utilisateur « ' . $request->string('nom') . ' » a été créé.');
    }

    public function edit(Request $request, string $id): void
    {
        $utilisateur = Utilisateur::find((int) $id);

        if ($utilisateur === null) {
            throw HttpException::notFound('Cet utilisateur est introuvable.');
        }

        $this->view('utilisateurs/form', [
            'pageTitle'   => 'Modifier « ' . $utilisateur['nom'] . ' »',
            'utilisateur' => $utilisateur,
            'roles'       => self::ROLES,
            'formAction'  => url('utilisateurs/' . (int) $utilisateur['idutilisateur']),
            'mode'        => 'edit',
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $utilisateurId = (int) $id;
        $utilisateur   = Utilisateur::find($utilisateurId);

        if ($utilisateur === null) {
            throw HttpException::notFound('Cet utilisateur est introuvable.');
        }

        $validator = Validator::make($request->all(), [
            'nom'   => 'required|minlength:2|maxlength:250',
            'email' => 'required|email|maxlength:250|unique:utilisateur,email,'
                . $utilisateurId . ',idutilisateur',
            'role'  => 'required|in:' . implode(',', self::ROLES),
        ]);

        if ($validator->fails()) {
            $this->fail('utilisateurs/' . $utilisateurId . '/editer', (string) $validator->firstError(), $request->all());
        }

        $data = [
            'nom'   => $request->string('nom'),
            'email' => $request->string('email'),
            'role'  => $request->string('role'),
        ];

        $newPassword = (string) $request->input('mdp', '');

        if ($newPassword !== '') {
            if (mb_strlen($newPassword) < 6) {
                $this->fail(
                    'utilisateurs/' . $utilisateurId . '/editer',
                    'Le mot de passe doit contenir au moins 6 caractères.',
                    $request->all()
                );
            }
            $data['mdp'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        Utilisateur::updateById($utilisateurId, $data);

        $this->succeed('utilisateurs', 'L\'utilisateur « ' . $request->string('nom') . ' » a été mis à jour.');
    }

    public function destroy(Request $request, string $id): void
    {
        $utilisateurId = (int) $id;

        if ($utilisateurId === Auth::id()) {
            $this->fail('utilisateurs', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $utilisateur = Utilisateur::find($utilisateurId);

        if ($utilisateur === null) {
            throw HttpException::notFound('Cet utilisateur est introuvable.');
        }

        if ($utilisateur['role'] === 'admin' && Utilisateur::countAdmins() <= 1) {
            $this->fail('utilisateurs', 'Impossible de supprimer le dernier administrateur.');
        }

        Utilisateur::deleteById($utilisateurId);

        $this->succeed('utilisateurs', 'L\'utilisateur « ' . $utilisateur['nom'] . ' » a été supprimé.');
    }
}