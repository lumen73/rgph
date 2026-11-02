<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#06392a">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%23c1121f'/%3E%3Ccircle cx='12' cy='12' r='8' fill='none' stroke='white' stroke-width='1.5'/%3E%3Cpath d='M4 12h16M12 4c2 2.5 3 5.2 3 8s-1 5.5-3 8c-2-2.5-3-5.2-3-8s1-5.5 3-8Z' fill='none' stroke='white' stroke-width='1.5'/%3E%3C/svg%3E">
    <title>Connexion · <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="rg-auth-body">
    <a class="rg-skip" href="#rg-auth-main">Aller au contenu principal</a>
    <!-- Décor : esplanade des Amazones en arrière-plan sur toute l'interface. -->
    <div class="rg-auth-scene" aria-hidden="true">
        <div class="rg-auth-bg"></div>
    </div>

    <main class="rg-auth-layout" id="rg-auth-main">
        <section class="rg-auth-panel">
            <?= $content ?>
        </section>

        <aside class="rg-auth-visual">
            <img class="rg-auth-statue"
                 src="<?= e(asset('img/amazone/statue-amazone.jpg')) ?>"
                 alt="" decoding="async">
        </aside>
    </main>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
