<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
?>
<div class="rg-auth-card">
    <div class="rg-auth-brand">
        <span class="rg-logo"><?= icon('globe', 'ico ico--xl') ?></span>
        <h1><?= e(config('app.name')) ?></h1>
        <p><?= e(config('app.subtitle')) ?></p>
    </div>

    <?php include VIEW_PATH . '/partials/flash.php'; ?>

    <form method="post" action="<?= url('login') ?>" class="rg-auth-form" novalidate>
        <?= csrf_field() ?>

        <div class="rg-field">
            <label class="rg-label" for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" class="rg-input"
                   value="<?= e(old('email')) ?>" placeholder="vous@exemple.org"
                   autocomplete="username" required autofocus>
        </div>

        <div class="rg-field">
            <label class="rg-label" for="mdp">Mot de passe</label>
            <input type="password" id="mdp" name="mdp" class="rg-input"
                   placeholder="••••••••" autocomplete="current-password" required>
        </div>

        <button type="submit" class="rg-btn rg-btn--primary rg-btn--block rg-btn--lg">
            <?= icon('logout') ?><span>Se connecter</span>
        </button>
    </form>

    <div class="rg-auth-rep">
        <span class="rg-auth-rep-flag">
            <?php $flagClass = 'rg-flag-svg--mini'; include VIEW_PATH . '/partials/drapeau-benin.php'; ?>
        </span>
        <span>République du Bénin</span>
    </div>

    <p class="rg-auth-foot">
        <?= icon('shield', 'ico ico--sm') ?> Accès sécurisé · <?= date('Y') ?>
    </p>
</div>
