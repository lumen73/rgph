<?php
declare(strict_types=1);

namespace App\Controllers;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;

/**
 * Authentification (connexion / déconnexion).
 */
final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        $this->view('auth/login', [
            'pageTitle' => 'Connexion',
        ], 'layouts/auth');
    }

    public function login(Request $request): void
    {
        // Garde-fou anti-force brute : au-delà de 6 échecs en 60 secondes,
        // la connexion est bloquée pour la session en cours.
        $attempts = Session::get('login_attempts', ['fails' => 0, 'last' => 0]);
        $fails    = (int) ($attempts['fails'] ?? 0);
        $last     = (int) ($attempts['last'] ?? 0);

        if ($fails >= 6 && (time() - $last) < 60) {
            $this->fail('login', 'Trop de tentatives. Réessayez dans '
                . (60 - (time() - $last)) . ' secondes.');
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'mdp'   => 'required',
        ]);

        if ($validator->fails()) {
            $this->fail('login', (string) $validator->firstError(), ['email' => $request->string('email')]);
        }

        $email    = $request->string('email');
        $password = (string) $request->input('mdp', '');

        if (!Auth::attempt($email, $password)) {
            Session::set('login_attempts', ['fails' => $fails + 1, 'last' => time()]);
            $this->fail('login', 'Identifiants incorrects.', ['email' => $email]);
        }

        Session::set('login_attempts', ['fails' => 0, 'last' => 0]);

        $user = Auth::user();

        $this->flash('success', 'Bienvenue ' . ($user['nom'] ?? $email) . ' !');
        $this->redirect('');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Session::flash('info', 'Vous avez été déconnecté.');

        $this->redirect('login');
    }
}