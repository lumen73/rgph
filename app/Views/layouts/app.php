<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var string $content */
/** @var string $pageTitle */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#06392a">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%23c1121f'/%3E%3Ccircle cx='12' cy='12' r='8' fill='none' stroke='white' stroke-width='1.5'/%3E%3Cpath d='M4 12h16M12 4c2 2.5 3 5.2 3 8s-1 5.5-3 8c-2-2.5-3-5.2-3-8s1-5.5 3-8Z' fill='none' stroke='white' stroke-width='1.5'/%3E%3C/svg%3E">
    <title><?= e($pageTitle ?? config('app.name')) ?> · <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="rg-body">
    <a class="rg-skip" href="#rg-content">Aller au contenu principal</a>
    <div class="rg-shell">
        <?php include VIEW_PATH . '/partials/sidebar.php'; ?>

        <div class="rg-main">
            <?php include VIEW_PATH . '/partials/topbar.php'; ?>

            <main class="rg-content" id="rg-content">
                <?php include VIEW_PATH . '/partials/flash.php'; ?>
                <?= $content ?>
            </main>
        </div>
    </div>

    <div class="rg-sidebar-backdrop" data-sidebar-backdrop></div>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>