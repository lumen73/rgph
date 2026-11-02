<?php
declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;

/**
 * ---------------------------------------------------------------------------
 * Fonctions utilitaires globales (disponibles dans toute l'application).
 * ---------------------------------------------------------------------------
 */

/**
 * Échappe une valeur pour un affichage HTML sûr (protection XSS).
 *
 * @param mixed $value
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * @param mixed $key
 * @param mixed $default
 * @return mixed
 */
function config(string $key, $default = null)
{
    return Config::get($key, $default);
}

function base_url(): string
{
    return (string) Config::get('app.base_url', '');
}

/**
 * Construit une URL interne à l'application.
 *
 * @param array<string, mixed> $query
 */
function url(string $path = '', array $query = []): string
{
    $path = trim($path, '/');

    if (Config::get('app.pretty_urls')) {
        $base = base_url();
        $href = $base . ($path === '' ? '/' : '/' . $path);
    } else {
        $href = base_url() . '/index.php';
        if ($path !== '') {
            $query = ['route' => $path] + $query;
        }
    }

    if ($query !== []) {
        $href .= (strpos($href, '?') === false ? '?' : '&') . http_build_query($query);
    }

    return $href;
}

/**
 * Action d'un formulaire en method="get".
 *
 * En GET, le navigateur remplace la query string de l'action par les champs du
 * formulaire : sans URLs jolies, l'action doit donc pointer vers `index.php`
 * et la route voyager dans un champ caché (voir `get_form_route_field()`).
 */
function get_form_action(string $path): string
{
    return Config::get('app.pretty_urls') ? url($path) : base_url() . '/index.php';
}

/**
 * Champ caché transportant la route d'un formulaire en method="get"
 * (nécessaire uniquement quand les URLs jolies sont désactivées).
 */
function get_form_route_field(string $path): string
{
    if (Config::get('app.pretty_urls')) {
        return '';
    }

    return '<input type="hidden" name="route" value="' . e(trim($path, '/')) . '">' . "\n";
}

/**
 * URL d'un asset statique, avec cache-busting automatique.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = BASE_PATH . '/public/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '';

    return base_url() . '/public/' . $path . ($version !== '' ? '?v=' . $version : '');
}

function csrf_field(): string
{
    return Csrf::field();
}

/**
 * Champ caché préservant la route lors d'une soumission de formulaire GET
 * (les navigateurs remplacent la query string de l'action).
 */
function route_field(string $route): string
{
    return '<input type="hidden" name="route" value="' . e($route) . '">';
}

/**
 * Retourne une ancienne saisie (après échec de validation).
 *
 * @param mixed $default
 * @return mixed
 */
function old(string $key, $default = '')
{
    $old = Session::oldInput();

    return $old[$key] ?? $default;
}

/**
 * @return array<string, mixed>|null
 */
function auth(): ?array
{
    return Auth::user();
}

function can(string $ability): bool
{
    return Auth::can($ability);
}

function current_route(): string
{
    $request = Request::current();

    return $request !== null ? $request->uri() : '';
}

/**
 * Retourne une classe CSS si la route courante commence par le préfixe donné.
 */
function active(string $prefix, string $class = 'is-active'): string
{
    $route = current_route();

    if ($prefix === '') {
        return $route === '' ? $class : '';
    }

    return strpos($route, $prefix) === 0 ? $class : '';
}

/**
 * Attribut d'accessibilité de la page courante pour les liens de navigation.
 */
function nav_page(string $prefix): string
{
    return active($prefix) !== '' ? ' aria-current="page"' : '';
}

/**
 * Formate un entier avec séparateur de milliers (espace insécable).
 *
 * @param mixed $value
 */
function format_number($value): string
{
    return number_format((float) $value, 0, ',', ' ');
}

/**
 * Construit les options d'un sélecteur de communes, groupées par département.
 *
 * Chaque option porte le nom du département dans un attribut data afin que
 * le département puisse être renseigné automatiquement côté navigateur.
 *
 * @param array<int, array<string, mixed>> $communes
 */
function commune_select_options(array $communes, int $selectedId = 0, string $placeholder = '— Choisir une commune —'): string
{
    $html = '<option value="">' . e($placeholder) . '</option>';
    $grouped = [];

    foreach ($communes as $commune) {
        $departement = (string) ($commune['departement'] ?? '');
        $grouped[$departement][] = $commune;
    }

    foreach ($grouped as $departement => $items) {
        $html .= '<optgroup label="' . e($departement) . '">';

        foreach ($items as $commune) {
            $id = (int) $commune['idcommune'];
            $html .= '<option value="' . $id . '"'
                . ' data-departement-id="' . (int) ($commune['iddepartement'] ?? 0) . '"'
                . ' data-departement-nom="' . e((string) ($commune['departement'] ?? '')) . '"'
                . ($selectedId === $id ? ' selected' : '') . '>'
                . e((string) $commune['nom'])
                . '</option>';
        }

        $html .= '</optgroup>';
    }

    return $html;
}

/**
 * Construit les options d'un sélecteur d'arrondissements, groupées par
 * département. Chaque option porte la commune et le département associés.
 *
 * @param array<int, array<string, mixed>> $arrondissements
 */
function arrondissement_select_options(
    array $arrondissements,
    int $selectedId = 0,
    string $placeholder = '— Aucun arrondissement —'
): string {
    $html = '<option value="">' . e($placeholder) . '</option>';
    $grouped = [];

    foreach ($arrondissements as $arrondissement) {
        $departement = (string) ($arrondissement['departement'] ?? '');
        $grouped[$departement][] = $arrondissement;
    }

    foreach ($grouped as $departement => $items) {
        $html .= '<optgroup label="' . e($departement) . '">';

        foreach ($items as $arrondissement) {
            $id = (int) $arrondissement['idarrondissement'];
            $html .= '<option value="' . $id . '"'
                . ' data-commune-id="' . (int) ($arrondissement['idcommune'] ?? 0) . '"'
                . ' data-commune-nom="' . e((string) ($arrondissement['commune'] ?? '')) . '"'
                . ' data-departement-id="' . (int) ($arrondissement['iddepartement'] ?? 0) . '"'
                . ' data-departement-nom="' . e((string) ($arrondissement['departement'] ?? '')) . '"'
                . ($selectedId === $id ? ' selected' : '') . '>'
                . e((string) $arrondissement['nom'] . ' — ' . (string) ($arrondissement['commune'] ?? ''))
                . '</option>';
        }

        $html .= '</optgroup>';
    }

    return $html;
}

/**
 * Construit les options d'un sélecteur de villes avec leur capacité.
 *
 * @param array<int, array<string, mixed>> $villes
 */
function ville_select_options(array $villes, int $selectedId = 0, string $placeholder = '— Choisir une ville —'): string
{
    $html = '<option value="">' . e($placeholder) . '</option>';
    $grouped = [];

    foreach ($villes as $ville) {
        $departement = (string) ($ville['departement'] ?? '');
        $grouped[$departement][] = $ville;
    }

    foreach ($grouped as $departement => $items) {
        $html .= '<optgroup label="' . e($departement) . '">';

        foreach ($items as $ville) {
            $id         = (int) $ville['idville'];
            $capacity   = (int) ($ville['population'] ?? 0);
            $registered = (int) ($ville['nb_registres'] ?? 0);
            $remaining  = max(0, $capacity - $registered);

            $html .= '<option value="' . $id . '"'
                . ' data-capacity="' . $capacity . '"'
                . ' data-registered="' . $registered . '"'
                . ' data-remaining="' . $remaining . '"'
                . ' data-commune="' . e((string) ($ville['commune'] ?? '')) . '"'
                . ' data-departement="' . e((string) ($ville['departement'] ?? '')) . '"'
                . ' data-arrondissement="' . e((string) ($ville['arrondissement'] ?? '')) . '"'
                . ($selectedId === $id ? ' selected' : '') . '>'
                . e((string) $ville['nom'] . ' — ' . (string) ($ville['commune'] ?? ''))
                . ' · reste ' . format_number($remaining) . ' / ' . format_number($capacity)
                . '</option>';
        }

        $html .= '</optgroup>';
    }

    return $html;
}

/**
 * Retourne le nom du département d'une commune à partir de la liste fournie.
 *
 * @param array<int, array<string, mixed>> $communes
 */
function commune_department_name(array $communes, int $communeId): string
{
    foreach ($communes as $commune) {
        if ((int) ($commune['idcommune'] ?? 0) === $communeId) {
            return (string) ($commune['departement'] ?? '');
        }
    }

    return '';
}

/**
 * Retourne la commune et le département d'un arrondissement.
 *
 * @param array<int, array<string, mixed>> $arrondissements
 * @return array<string, string>
 */
function arrondissement_location(array $arrondissements, int $arrondissementId): array
{
    foreach ($arrondissements as $arrondissement) {
        if ((int) ($arrondissement['idarrondissement'] ?? 0) === $arrondissementId) {
            return [
                'commune'     => (string) ($arrondissement['commune'] ?? ''),
                'departement' => (string) ($arrondissement['departement'] ?? ''),
            ];
        }
    }

    return ['commune' => '', 'departement' => ''];
}

/**
 * Construit les liens de pagination.
 *
 * @param array<string, mixed> $query
 */
function paginate_links(\App\Core\Paginator $paginator, string $route, array $query = [], int $window = 2): string
{
    if (!$paginator->hasPages()) {
        return '';
    }

    $current = $paginator->currentPage();
    $last    = $paginator->lastPage();

    $make = static function (int $page) use ($route, $query): string {
        return url($route, array_merge($query, ['page' => $page]));
    };

    $html = '<nav class="rg-pagination" aria-label="Navigation entre les pages"><ul>';

    // Bouton « précédent ».
    if ($current <= 1) {
        $html .= '<li><span class="rg-page is-disabled">' . icon('chevron-left', 'ico ico--sm') . '</span></li>';
    } else {
        $html .= '<li><a class="rg-page" href="' . e($make($current - 1)) . '" rel="prev">' . icon('chevron-left', 'ico ico--sm') . '</a></li>';
    }

    $start = max(1, $current - $window);
    $end   = min($last, $current + $window);

    if ($start > 1) {
        $html .= '<li><a class="rg-page" href="' . e($make(1)) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li><span class="rg-page is-gap">…</span></li>';
        }
    }

    for ($page = $start; $page <= $end; $page++) {
        if ($page === $current) {
            $html .= '<li><span class="rg-page is-active" aria-current="page">' . $page . '</span></li>';
        } else {
            $html .= '<li><a class="rg-page" href="' . e($make($page)) . '">' . $page . '</a></li>';
        }
    }

    if ($end < $last) {
        if ($end < $last - 1) {
            $html .= '<li><span class="rg-page is-gap">…</span></li>';
        }
        $html .= '<li><a class="rg-page" href="' . e($make($last)) . '">' . $last . '</a></li>';
    }

    // Bouton « suivant ».
    if ($current >= $last) {
        $html .= '<li><span class="rg-page is-disabled">' . icon('chevron-right', 'ico ico--sm') . '</span></li>';
    } else {
        $html .= '<li><a class="rg-page" href="' . e($make($current + 1)) . '" rel="next">' . icon('chevron-right', 'ico ico--sm') . '</a></li>';
    }

    return $html . '</ul></nav>';
}

/**
 * Icône SVG (feather-style) en ligne.
 */
function icon(string $name, string $class = 'ico'): string
{
    static $paths = [
        'home'         => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'grid'         => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
        'map-pin'      => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'layers'       => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
        'users'        => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'chart'        => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'trending'     => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
        'plus'         => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'search'       => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'edit'         => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'trash'        => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'download'     => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'logout'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'chevron-left' => '<polyline points="15 18 9 12 15 6"/>',
        'chevron-right'=> '<polyline points="9 18 15 12 9 6"/>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
        'chevron-up'   => '<polyline points="18 15 12 9 6 15"/>',
        'menu'         => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'close'        => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'check'        => '<polyline points="20 6 9 17 4 12"/>',
        'alert'        => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'info'         => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'building'     => '<path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/>',
        'map'          => '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>',
        'globe'        => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'filter'       => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'save'         => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
        'user'         => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'file'         => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'shield'       => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'circle'       => '<circle cx="12" cy="12" r="10"/>',
    ];

    $path = $paths[$name] ?? $paths['circle'];

    return '<svg class="' . e($class) . '" width="18" height="18" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
        . 'aria-hidden="true">' . $path . '</svg>';
}