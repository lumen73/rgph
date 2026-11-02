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
 * Rapports statistiques.
 */
final class RapportController extends Controller
{
    public function index(Request $request): void
    {
        $stats = Ville::stats();

        $this->view('rapports/index', [
            'pageTitle'      => 'Rapports',
            'population'     => (int) $stats['population'],
            'nbVilles'       => (int) $stats['total'],
            'nbDepartements' => Departement::countWhere(),
            'nbCommunes'     => Commune::countWhere(),
            'parDepartement' => Ville::populationByDepartement(),
            'topVilles'      => Ville::topVilles(10),
        ]);
    }

    public function departements(Request $request): void
    {
        $rows = Ville::populationByDepartement();

        $this->view('rapports/departements', [
            'pageTitle' => 'Population par département',
            'rows'      => $rows,
        ]);
    }

    public function communes(Request $request): void
    {
        $limit = max(1, min(200, $request->int('limite', 20)));

        $this->view('rapports/communes', [
            'pageTitle' => 'Population par commune',
            'rows'      => Ville::populationByCommune($limit),
            'limit'     => $limit,
        ]);
    }

    public function villes(Request $request): void
    {
        $limit = max(1, min(100, $request->int('limite', 15)));

        $this->view('rapports/villes', [
            'pageTitle' => 'Villes les plus peuplées',
            'rows'      => Ville::topVilles($limit),
            'limit'     => $limit,
        ]);
    }

    public function registre(Request $request): void
    {
        $this->view('rapports/registre', [
            'pageTitle'                  => 'Profils du registre',
            'registreStats'              => Registre::stats(),
            'registreParVille'           => Registre::byVille(),
            'registreParArrondissement'  => Registre::byArrondissement(),
            'registreParCommune'         => Registre::byCommune(),
            'registreParDepartement'     => Registre::byDepartement(),
            'registreSansArrondissement' => Registre::byArrondissementNonRattache(),
        ]);
    }
}