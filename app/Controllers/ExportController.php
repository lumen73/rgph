<?php
declare(strict_types=1);

namespace App\Controllers;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Core\Controller;
use App\Core\Request;
use App\Models\Arrondissement;
use App\Models\Commune;
use App\Models\Registre;
use App\Models\Ville;

/**
 * Export des données au format CSV.
 *
 * Les fichiers sont encodés en UTF-8 avec BOM et utilisent le point-virgule
 * comme séparateur : ils s'ouvrent directement dans Excel / LibreOffice.
 */
final class ExportController extends Controller
{
    public function villes(Request $request): void
    {
        $rows = [];

        foreach (Ville::allForExport() as $row) {
            $rows[] = [
                'Département'    => $row['departement'],
                'Commune'        => $row['commune'],
                'Arrondissement' => $row['arrondissement'] ?? '',
                'Ville'          => $row['nom'],
                'Population'     => $row['population'],
            ];
        }

        $this->streamCsv(
            'villes_rgph5_' . date('Y-m-d_His') . '.csv',
            ['Département', 'Commune', 'Arrondissement', 'Ville', 'Population'],
            $rows
        );
    }

    public function communes(Request $request): void
    {
        $rows = [];

        foreach (Commune::allForExport() as $row) {
            $rows[] = [
                'Département'      => $row['departement'],
                'Commune'          => $row['commune'],
                'Arrondissements'  => $row['nb_arrondissements'],
                'Villes'           => $row['nb_villes'],
                'Population'       => $row['population'],
            ];
        }

        $this->streamCsv(
            'communes_rgph5_' . date('Y-m-d_His') . '.csv',
            ['Département', 'Commune', 'Arrondissements', 'Villes', 'Population'],
            $rows
        );
    }

    public function arrondissements(Request $request): void
    {
        $rows = [];

        foreach (Arrondissement::allForExport() as $row) {
            $rows[] = [
                'Département'   => $row['departement'],
                'Commune'       => $row['commune'],
                'Arrondissement' => $row['arrondissement'],
                'Villes'        => $row['nb_villes'] ?? 0,
            ];
        }

        $this->streamCsv(
            'arrondissements_rgph5_' . date('Y-m-d_His') . '.csv',
            ['Département', 'Commune', 'Arrondissement', 'Villes'],
            $rows
        );
    }

    public function departements(Request $request): void
    {
        $rows = [];

        foreach (Ville::populationByDepartement() as $row) {
            $rows[] = [
                'Département' => $row['nom'],
                'Communes'    => $row['nb_communes'],
                'Villes'      => $row['nb_villes'],
                'Population'  => $row['population'],
            ];
        }

        $this->streamCsv(
            'population_par_departement_' . date('Y-m-d_His') . '.csv',
            ['Département', 'Communes', 'Villes', 'Population'],
            $rows
        );
    }

    public function registres(Request $request): void
    {
        $rows = [];

        foreach (Registre::allForExport() as $row) {
            $rows[] = [
                'Département'      => $row['departement'],
                'Commune'          => $row['commune'],
                'Arrondissement'   => $row['arrondissement'] ?? '',
                'Ville'            => $row['ville'],
                'Nom'              => $row['nom'],
                'Prénom'           => $row['prenom'],
                'Date de référence' => $row['date_ref'],
                'Âge'              => $row['age'],
                'Genre'            => $row['genre'],
                'Profil'           => (int) $row['age'] < 18 ? 'Junior' : 'Adulte',
            ];
        }

        $this->streamCsv(
            'registre_rgph5_' . date('Y-m-d_His') . '.csv',
            [
                'Département', 'Commune', 'Arrondissement', 'Ville', 'Nom', 'Prénom',
                'Date de référence', 'Âge', 'Genre', 'Profil',
            ],
            $rows
        );
    }
}
