<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
?>
<aside class="rg-sidebar" data-sidebar>
    <div class="rg-brand">
        <span class="rg-brand-mark"><?= icon('globe', 'ico ico--lg') ?></span>
        <span class="rg-brand-text">
            <strong><?= e(config('app.name')) ?></strong>
            <small>Recensement</small>
        </span>
    </div>

    <nav class="rg-nav">
        <span class="rg-nav-label">Pilotage</span>

        <a href="<?= url('') ?>" class="rg-nav-item <?= active('') ?>"<?= nav_page('') ?>>
            <?= icon('grid') ?><span>Tableau de bord</span>
        </a>

        <span class="rg-nav-label">Données</span>

        <a href="<?= url('villes') ?>" class="rg-nav-item <?= active('villes') ?>"<?= nav_page('villes') ?>>
            <?= icon('map-pin') ?><span>Villes</span>
        </a>

        <a href="<?= url('communes') ?>" class="rg-nav-item <?= active('communes') ?>"<?= nav_page('communes') ?>>
            <?= icon('building') ?><span>Communes</span>
        </a>

        <a href="<?= url('arrondissements') ?>" class="rg-nav-item <?= active('arrondissements') ?>"<?= nav_page('arrondissements') ?>>
            <?= icon('map') ?><span>Arrondissements</span>
        </a>

        <?php if (can('registre.view')): ?>
            <a href="<?= url('registres') ?>" class="rg-nav-item <?= active('registres') ?>"<?= nav_page('registres') ?>>
                <?= icon('users') ?><span>Registre</span>
            </a>
        <?php endif; ?>

        <a href="<?= url('departements') ?>" class="rg-nav-item <?= active('departements') ?>"<?= nav_page('departements') ?>>
            <?= icon('layers') ?><span>Départements</span>
        </a>

        <span class="rg-nav-label">Analyse</span>

        <a href="<?= url('rapports') ?>" class="rg-nav-item <?= active('rapports') ?>"<?= nav_page('rapports') ?>>
            <?= icon('chart') ?><span>Rapports</span>
        </a>

        <?php if (can('user.manage')): ?>
            <span class="rg-nav-label">Administration</span>
            <a href="<?= url('utilisateurs') ?>" class="rg-nav-item <?= active('utilisateurs') ?>"<?= nav_page('utilisateurs') ?>>
                <?= icon('users') ?><span>Utilisateurs</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="rg-sidebar-foot">
        <a href="<?= url('logout') ?>" class="rg-nav-item rg-nav-item--danger">
            <?= icon('logout') ?><span>Déconnexion</span>
        </a>
    </div>
</aside>