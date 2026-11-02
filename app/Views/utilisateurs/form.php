<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<string, mixed>|null $utilisateur */
/** @var array<int, string> $roles */
/** @var string $formAction */
/** @var string $mode */

$isEdit     = $mode === 'edit';
$roleLabels = ['admin' => 'Administrateur', 'agent' => 'Agent', 'lecteur' => 'Lecteur'];
?>
<div class="rg-form-page">
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2><?= $isEdit ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' ?></h2>
        <p class="rg-muted">Le rôle détermine les permissions accordées dans l'application.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('utilisateurs') ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Retour</span></a>
    </div>
</div>

<div class="rg-card rg-card--form rg-card--narrow">
    <form method="post" action="<?= e($formAction) ?>" class="rg-form">
        <?= csrf_field() ?>

        <div class="rg-field">
            <label class="rg-label" for="nom">Nom complet <span class="rg-req">*</span></label>
            <input type="text" id="nom" name="nom" class="rg-input"
                   value="<?= e(old('nom', $utilisateur['nom'] ?? '')) ?>" placeholder="Ex. Jean Dupont" required maxlength="250" autofocus>
        </div>

        <div class="rg-field">
            <label class="rg-label" for="email">Adresse e-mail <span class="rg-req">*</span></label>
            <input type="email" id="email" name="email" class="rg-input"
                   value="<?= e(old('email', $utilisateur['email'] ?? '')) ?>" placeholder="vous@exemple.org" required maxlength="250">
        </div>

        <div class="rg-field">
            <label class="rg-label" for="role">Rôle <span class="rg-req">*</span></label>
            <select id="role" name="role" class="rg-select" required>
                <?php $selectedRole = old('role', $utilisateur['role'] ?? 'agent'); ?>
                <?php foreach ($roles as $roleItem): ?>
                    <option value="<?= e($roleItem) ?>" <?= $selectedRole === $roleItem ? 'selected' : '' ?>>
                        <?= e($roleLabels[$roleItem] ?? $roleItem) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="rg-hint">Administrateur : tous les droits · Agent : saisie et modification · Lecteur : consultation seule.</p>
        </div>

        <div class="rg-field">
            <label class="rg-label" for="mdp">Mot de passe <?= $isEdit ? '' : '<span class="rg-req">*</span>' ?></label>
            <input type="password" id="mdp" name="mdp" class="rg-input"
                   placeholder="<?= $isEdit ? 'Laisser vide pour ne pas modifier' : 'Au moins 6 caractères' ?>"
                   autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
            <?php if ($isEdit): ?>
                <p class="rg-hint">Laissez ce champ vide pour conserver le mot de passe actuel.</p>
            <?php endif; ?>
        </div>

        <div class="rg-form-actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('save') ?><span><?= $isEdit ? 'Enregistrer les modifications' : 'Créer l\'utilisateur' ?></span></button>
            <a href="<?= url('utilisateurs') ?>" class="rg-btn rg-btn--ghost">Annuler</a>
        </div>
    </form>
</div>
</div>