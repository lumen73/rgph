<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<string, mixed>|null $departement */
/** @var string $formAction */
/** @var string $mode */

$isEdit = $mode === 'edit';
?>
<div class="rg-form-page">
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2><?= $isEdit ? 'Modifier le département' : 'Nouveau département' ?></h2>
        <p class="rg-muted">Le département est le niveau administratif supérieur.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('departements') ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Retour</span></a>
    </div>
</div>

<div class="rg-card rg-card--form rg-card--narrow">
    <form method="post" action="<?= e($formAction) ?>" class="rg-form">
        <?= csrf_field() ?>

        <div class="rg-field">
            <label class="rg-label" for="nom">Nom du département</label>
            <input type="text" id="nom" name="nom" class="rg-input"
                   value="<?= e(old('nom', $departement['nom'] ?? '')) ?>" placeholder="Ex. Littoral" required maxlength="255" autofocus>
        </div>

        <div class="rg-form-actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('save') ?><span><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter le département' ?></span></button>
            <a href="<?= url('departements') ?>" class="rg-btn rg-btn--ghost">Annuler</a>
        </div>
    </form>
</div>
</div>