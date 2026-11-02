<?php
declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Core\Router;

/** @var Router $router */

// --- Authentification -------------------------------------------------------
$router->get('login', 'AuthController@showLogin', ['guest' => true]);
$router->post('login', 'AuthController@login', ['guest' => true]);
$router->get('logout', 'AuthController@logout');

// --- Tableau de bord --------------------------------------------------------
$router->get('', 'DashboardController@index', ['auth' => true]);

// --- Villes -----------------------------------------------------------------
$router->get('villes', 'VilleController@index', ['auth' => true, 'can' => 'ville.view']);
$router->get('villes/creer-multiple', 'VilleController@createMultiple', ['auth' => true, 'can' => 'ville.create']);
$router->get('villes/creer', 'VilleController@create', ['auth' => true, 'can' => 'ville.create']);
$router->post('villes/multiple', 'VilleController@storeMultiple', ['auth' => true, 'can' => 'ville.create']);
$router->post('villes', 'VilleController@store', ['auth' => true, 'can' => 'ville.create']);
$router->get('villes/{id}', 'VilleController@show', ['auth' => true, 'can' => 'ville.view']);
$router->get('villes/{id}/editer', 'VilleController@edit', ['auth' => true, 'can' => 'ville.edit']);
$router->post('villes/{id}', 'VilleController@update', ['auth' => true, 'can' => 'ville.edit']);
$router->post('villes/{id}/supprimer', 'VilleController@destroy', ['auth' => true, 'can' => 'ville.delete']);

// --- Communes ---------------------------------------------------------------
$router->get('communes', 'CommuneController@index', ['auth' => true, 'can' => 'commune.view']);
$router->get('communes/creer-multiple', 'CommuneController@createMultiple', ['auth' => true, 'can' => 'commune.create']);
$router->get('communes/creer', 'CommuneController@create', ['auth' => true, 'can' => 'commune.create']);
$router->post('communes/multiple', 'CommuneController@storeMultiple', ['auth' => true, 'can' => 'commune.create']);
$router->post('communes', 'CommuneController@store', ['auth' => true, 'can' => 'commune.create']);
$router->get('communes/{id}/editer', 'CommuneController@edit', ['auth' => true, 'can' => 'commune.edit']);
$router->post('communes/{id}', 'CommuneController@update', ['auth' => true, 'can' => 'commune.edit']);
$router->post('communes/{id}/supprimer', 'CommuneController@destroy', ['auth' => true, 'can' => 'commune.delete']);

// --- Arrondissements --------------------------------------------------------
$router->get('arrondissements', 'ArrondissementController@index', ['auth' => true, 'can' => 'arrondissement.view']);
$router->get('arrondissements/creer-multiple', 'ArrondissementController@createMultiple', ['auth' => true, 'can' => 'arrondissement.create']);
$router->get('arrondissements/creer', 'ArrondissementController@create', ['auth' => true, 'can' => 'arrondissement.create']);
$router->post('arrondissements/multiple', 'ArrondissementController@storeMultiple', ['auth' => true, 'can' => 'arrondissement.create']);
$router->post('arrondissements', 'ArrondissementController@store', ['auth' => true, 'can' => 'arrondissement.create']);
$router->get('arrondissements/{id}/editer', 'ArrondissementController@edit', ['auth' => true, 'can' => 'arrondissement.edit']);
$router->post('arrondissements/{id}', 'ArrondissementController@update', ['auth' => true, 'can' => 'arrondissement.edit']);
$router->post('arrondissements/{id}/supprimer', 'ArrondissementController@destroy', ['auth' => true, 'can' => 'arrondissement.delete']);

// --- Registre ---------------------------------------------------------------
$router->get('registres', 'RegistreController@index', ['auth' => true, 'can' => 'registre.view']);
$router->get('registres/creer', 'RegistreController@create', ['auth' => true, 'can' => 'registre.create']);
$router->post('registres', 'RegistreController@store', ['auth' => true, 'can' => 'registre.create']);
$router->get('registres/{id}/editer', 'RegistreController@edit', ['auth' => true, 'can' => 'registre.edit']);
$router->post('registres/{id}', 'RegistreController@update', ['auth' => true, 'can' => 'registre.edit']);
$router->post('registres/{id}/supprimer', 'RegistreController@destroy', ['auth' => true, 'can' => 'registre.delete']);

// --- Départements -----------------------------------------------------------
$router->get('departements', 'DepartementController@index', ['auth' => true, 'can' => 'departement.view']);
$router->get('departements/creer', 'DepartementController@create', ['auth' => true, 'can' => 'departement.manage']);
$router->post('departements', 'DepartementController@store', ['auth' => true, 'can' => 'departement.manage']);
$router->get('departements/{id}/editer', 'DepartementController@edit', ['auth' => true, 'can' => 'departement.manage']);
$router->post('departements/{id}', 'DepartementController@update', ['auth' => true, 'can' => 'departement.manage']);
$router->post('departements/{id}/supprimer', 'DepartementController@destroy', ['auth' => true, 'can' => 'departement.manage']);

// --- Utilisateurs -----------------------------------------------------------
$router->get('utilisateurs', 'UtilisateurController@index', ['auth' => true, 'can' => 'user.manage']);
$router->get('utilisateurs/creer', 'UtilisateurController@create', ['auth' => true, 'can' => 'user.manage']);
$router->post('utilisateurs', 'UtilisateurController@store', ['auth' => true, 'can' => 'user.manage']);
$router->get('utilisateurs/{id}/editer', 'UtilisateurController@edit', ['auth' => true, 'can' => 'user.manage']);
$router->post('utilisateurs/{id}', 'UtilisateurController@update', ['auth' => true, 'can' => 'user.manage']);
$router->post('utilisateurs/{id}/supprimer', 'UtilisateurController@destroy', ['auth' => true, 'can' => 'user.manage']);

// --- Rapports ---------------------------------------------------------------
$router->get('rapports', 'RapportController@index', ['auth' => true, 'can' => 'rapport.view']);
$router->get('rapports/departements', 'RapportController@departements', ['auth' => true, 'can' => 'rapport.view']);
$router->get('rapports/communes', 'RapportController@communes', ['auth' => true, 'can' => 'rapport.view']);
$router->get('rapports/villes', 'RapportController@villes', ['auth' => true, 'can' => 'rapport.view']);
$router->get('rapports/registre', 'RapportController@registre', ['auth' => true, 'can' => 'rapport.view']);

// --- Exports ----------------------------------------------------------------
$router->get('export/villes', 'ExportController@villes', ['auth' => true, 'can' => 'export']);
$router->get('export/communes', 'ExportController@communes', ['auth' => true, 'can' => 'export']);
$router->get('export/arrondissements', 'ExportController@arrondissements', ['auth' => true, 'can' => 'export']);
$router->get('export/departements', 'ExportController@departements', ['auth' => true, 'can' => 'export']);
$router->get('export/registres', 'ExportController@registres', ['auth' => true, 'can' => 'export']);

// --- API (AJAX) -------------------------------------------------------------
$router->get('api/communes', 'ApiController@communes', ['auth' => true]);
$router->get('api/villes', 'ApiController@villes', ['auth' => true]);