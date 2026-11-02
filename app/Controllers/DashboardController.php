<?php
declare(strict_types=1);

namespace App\Controllers;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Core\Controller;
use App\Core\Request;
use App\Models\Commune;
use App\Models\Departement;
use App\Models\Registre;
use App\Models\Ville;

/**
 * Tableau de bord : indicateurs clés et raccourcis.
 */
final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $stats = Ville::stats();

        $this->view('dashboard/index', [
            'pageTitle'       => 'Tableau de bord',
            'nbDepartements'  => Departement::countWhere(),
            'nbCommunes'      => Commune::countWhere(),
            'nbVilles'        => (int) $stats['total'],
            'population'      => (int) $stats['population'],
            'populationMoy'   => (int) round((float) $stats['moyenne']),
            'populationMax'   => (int) $stats['maximum'],
            'parDepartement'  => Ville::populationByDepartement(),
            'topVilles'       => Ville::topVilles(5),
            'villesRecentes'  => Ville::recents(5),
            'registreStats'   => Registre::stats(),
            'registreParVille' => Registre::byVille(),
            'registreParArrondissement' => Registre::byArrondissement(),
            'registreParCommune' => Registre::byCommune(),
            'registreParDepartement' => Registre::byDepartement(),
            'registreSansArrondissement' => Registre::byArrondissementNonRattache(),
        ]);
    }
}