<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

/**
 * Drapeau de la République du Bénin (2:3) animé « au vent ».
 *
 * Le drapeau est découpé en colonnes verticales indépendantes qui oscillent
 * avec une amplitude croissante vers le bord libre et un léger décalage de
 * phase. Les colonnes ne sont pas rognées : le haut et le bas du drapeau
 * ondulent réellement, comme un tissu qui claque.
 *
 * @var string $flagClass Classe CSS additionnelle (optionnelle).
 */
$flagClass  = isset($flagClass) ? (string) $flagClass : '';
$flagUid    = 'rg-flag-' . substr(md5(uniqid('bj', true)), 0, 8);
$flagWidth  = 300;
$flagHeight = 200;
$greenWidth = 120; // Bande verte à la hampe : 2/5 de la longueur.
$columns    = 14;
$columnW    = $flagWidth / $columns;

$colors = [
    'green'  => '#008850',
    'yellow' => '#FCD20F',
    'red'    => '#E90929',
    'shade'  => 'url(#' . $flagUid . '-shade)',
];
?>
<svg class="rg-flag-svg <?= e($flagClass) ?>" viewBox="0 0 <?= $flagWidth ?> <?= $flagHeight ?>"
     xmlns="http://www.w3.org/2000/svg" role="img"
     aria-label="Drapeau de la République du Bénin : bande verte à la hampe, bandes jaune et rouge au vol">
    <defs>
        <linearGradient id="<?= $flagUid ?>-shade" gradientUnits="userSpaceOnUse"
                        x1="0" y1="0" x2="<?= $flagWidth ?>" y2="0">
            <stop offset="0" stop-color="#06231a" stop-opacity=".26"></stop>
            <stop offset=".3" stop-color="#06231a" stop-opacity="0"></stop>
            <stop offset=".68" stop-color="#06231a" stop-opacity=".03"></stop>
            <stop offset="1" stop-color="#06231a" stop-opacity=".3"></stop>
        </linearGradient>
    </defs>

    <g fill="#111">
        <?php for ($i = 0; $i < $columns; $i++): ?>
            <?php
            $left  = $i * $columnW - 1.5;
            $right = $left + $columnW + 2.5;
            $amp   = round(1.2 + ($i * 1.05), 2);
            $delay = round(-0.17 * $i, 2);
            // Pli diagonal : cisaillement proportionnel à l'amplitude.
            $skew  = round($amp * 0.25, 2);

            // L'ombrage est strictement non chevauchant : deux rects qui se
            // superposent verraient leur dégradé s'additionner (coutures sombres).
            $shadeLeft = $i * $columnW;
            $shadeW    = $columnW + 0.5;

            $greenLeft  = max($left, 0);
            $greenRight = min($right, $greenWidth);
            $flyLeft    = max($left, $greenWidth);
            $flyRight   = $right;
            ?>
            <g class="rg-flag-col" style="--rg-flag-amp: <?= $amp ?>px; --rg-flag-skew: <?= $skew ?>deg; animation-delay: <?= $delay ?>s;">
                <?php if ($greenRight > $greenLeft): ?>
                    <?php $w = $greenRight - $greenLeft; ?>
                    <rect x="<?= e((string) $greenLeft) ?>" y="0" width="<?= e((string) $w) ?>"
                          height="<?= $flagHeight ?>" fill="<?= $colors['green'] ?>"></rect>
                    <rect x="<?= e((string) $shadeLeft) ?>" y="0" width="<?= e((string) $shadeW) ?>"
                          height="<?= $flagHeight ?>" fill="<?= $colors['shade'] ?>"></rect>
                <?php endif; ?>
                <?php if ($flyRight > $flyLeft): ?>
                    <?php $w = $flyRight - $flyLeft; ?>
                    <rect x="<?= e((string) $flyLeft) ?>" y="0" width="<?= e((string) $w) ?>"
                          height="<?= $flagHeight / 2 ?>" fill="<?= $colors['yellow'] ?>"></rect>
                    <rect x="<?= e((string) $flyLeft) ?>" y="0" width="<?= e((string) $w) ?>"
                          height="<?= $flagHeight / 2 ?>" fill="<?= $colors['shade'] ?>"></rect>
                    <rect x="<?= e((string) $flyLeft) ?>" y="<?= $flagHeight / 2 ?>" width="<?= e((string) $w) ?>"
                          height="<?= $flagHeight / 2 ?>" fill="<?= $colors['red'] ?>"></rect>
                    <rect x="<?= e((string) $flyLeft) ?>" y="<?= $flagHeight / 2 ?>" width="<?= e((string) $w) ?>"
                          height="<?= $flagHeight / 2 ?>" fill="<?= $colors['shade'] ?>"></rect>
                <?php endif; ?>
            </g>
        <?php endfor; ?>
    </g>
</svg>
