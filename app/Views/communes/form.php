<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<string, mixed>|null $commune */
/** @var array<int, array<string, mixed>> $departements */
/** @var string $formAction */
/** @var string $mode */

$isEdit              = $mode === 'edit';
$selectedDepartement = (int) old('iddepartement', $commune['iddepartement'] ?? 0);
$backUrl             = url('communes');
?>
<div class="rg-form-page">
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2><?= $isEdit ? 'Modifier la commune' : 'Nouvelle commune' ?></h2>
        
    </div>
    <div class="rg-actions">
        <a href="<?= e($backUrl) ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Retour</span></a>
    </div>
</div>

<div class="rg-card rg-card--form rg-card--narrow">
    <form method="post" action="<?= e($formAction) ?>" class="rg-form">
        <?= csrf_field() ?>

        <div class="rg-field">
            <label class="rg-label" for="nom">Nom de la commune</label>
            <input type="text" id="nom" name="nom" class="rg-input"
                   value="<?= e(old('nom', $commune['nom'] ?? '')) ?>" placeholder="Ex. Cotonou" required maxlength="250" autofocus>
        </div>

        <div class="rg-field">
            <label class="rg-label" for="iddepartement">Département</label>
            <select id="iddepartement" name="iddepartement" class="rg-select" required>
                <option value="">— Choisir un département —</option>
                <?php foreach ($departements as $departement): ?>
                    <option value="<?= (int) $departement['iddepartement'] ?>" <?= $selectedDepartement === (int) $departement['iddepartement'] ? 'selected' : '' ?>>
                        <?= e($departement['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="rg-form-actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('save') ?><span><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter la commune' ?></span></button>
            <a href="<?= e($backUrl) ?>" class="rg-btn rg-btn--ghost">Annuler</a>
        </div>
    </form>
</div>
</div>