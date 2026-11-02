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
use App\Models\Ville;

/**
 * Points d'entrée JSON pour les interactions AJAX.
 */
final class ApiController extends Controller
{
    /**
     * Communes d'un département (listes en cascade).
     */
    public function communes(Request $request): void
    {
        $departementId = $request->int('departement');
        $communes      = $departementId > 0 ? Commune::byDepartement($departementId) : [];

        $results = [];
        foreach ($communes as $commune) {
            $results[] = [
                'id'   => (int) $commune['idcommune'],
                'text' => (string) $commune['nom'],
            ];
        }

        $this->json(['results' => $results]);
    }

    /**
     * Recherche instantanée de villes.
     */
    public function villes(Request $request): void
    {
        $term = $request->string('q');

        if (mb_strlen($term) < 2) {
            $this->json(['results' => []]);
        }

        $villes  = Ville::search($term, 15);
        $results = [];

        foreach ($villes as $ville) {
            $results[] = [
                'id'         => (int) $ville['idville'],
                'nom'        => (string) $ville['nom'],
                'population' => (int) $ville['population'],
                'commune'    => (string) $ville['commune'],
                'departement'=> (string) $ville['departement'],
                'url'        => url('villes/' . (int) $ville['idville']),
            ];
        }

        $this->json(['results' => $results]);
    }
}