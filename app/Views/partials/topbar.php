<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<string, mixed>|null $currentUser */
$initial = mb_strtoupper(mb_substr((string) ($currentUser['nom'] ?? 'U'), 0, 1));
$roleLabel = ['admin' => 'Administrateur', 'agent' => 'Agent', 'lecteur' => 'Lecteur'][$currentUser['role'] ?? ''] ?? 'Utilisateur';
?>
<header class="rg-topbar">
    <button type="button" class="rg-icon-btn" data-sidebar-toggle aria-label="Ouvrir le menu">
        <?= icon('menu') ?>
    </button>

    <div class="rg-topbar-title">
        <h1><?= e($pageTitle ?? '') ?></h1>
    </div>

    <div class="rg-user" data-dropdown>
        <button type="button" class="rg-user-btn" data-dropdown-toggle aria-haspopup="true" aria-expanded="false">
            <span class="rg-avatar"><?= e($initial) ?></span>
            <span class="rg-user-info">
                <strong><?= e($currentUser['nom'] ?? 'Utilisateur') ?></strong>
                <small><?= e($roleLabel) ?></small>
            </span>
            <?= icon('chevron-down', 'ico ico--sm') ?>
        </button>
        <div class="rg-dropdown">
            <div class="rg-dropdown-head">
                <strong><?= e($currentUser['nom'] ?? '') ?></strong>
                <small><?= e($currentUser['email'] ?? '') ?></small>
            </div>
            <a href="<?= url('logout') ?>" class="rg-dropdown-item">
                <?= icon('logout') ?><span>Déconnexion</span>
            </a>
        </div>
    </div>
</header>