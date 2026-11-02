<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var int $status */
/** @var string $message */
$labels = [
    403 => 'Accès refusé',
    404 => 'Page introuvable',
    500 => 'Erreur interne',
];
$title = $labels[$status] ?? 'Erreur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> · <?= e(config('app.name')) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#06392a">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%23c1121f'/%3E%3Ccircle cx='12' cy='12' r='8' fill='none' stroke='white' stroke-width='1.5'/%3E%3Cpath d='M4 12h16M12 4c2 2.5 3 5.2 3 8s-1 5.5-3 8c-2-2.5-3-5.2-3-8s1-5.5 3-8Z' fill='none' stroke='white' stroke-width='1.5'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="rg-auth-body">
    <div class="rg-auth-scene" aria-hidden="true">
        <div class="rg-auth-bg"></div>
    </div>
    <main class="rg-auth-layout rg-auth-layout--single">
        <section class="rg-auth-panel">
            <div class="rg-auth-card rg-error-card">
                <span class="rg-error-code"><?= e((string) $status) ?></span>
                <h1><?= e($title) ?></h1>
                <p><?= e($message) ?></p>
                <a href="<?= url('') ?>" class="rg-btn rg-btn--primary">
                    <?= icon('home') ?><span>Retour au tableau de bord</span>
                </a>
            </div>
        </section>
    </main>
</body>
</html>