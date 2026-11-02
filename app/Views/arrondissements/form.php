<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<string, mixed>|null $arrondissement */
/** @var array<int, array<string, mixed>> $communes */
/** @var string $formAction */
/** @var string $mode */

$isEdit          = $mode === 'edit';
$selectedCommune = (int) old('idcommune', $arrondissement['idcommune'] ?? 0);
$departementNom  = commune_department_name($communes, $selectedCommune);
$backUrl         = url('arrondissements');
?>
<div class="rg-form-page">
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2><?= $isEdit ? 'Modifier l\'arrondissement' : 'Nouvel arrondissement' ?></h2>
        <p class="rg-muted">Choisissez la commune : le département se renseigne automatiquement.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= e($backUrl) ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Retour</span></a>
    </div>
</div>

<div class="rg-card rg-card--form rg-card--narrow">
    <form method="post" action="<?= e($formAction) ?>" class="rg-form">
        <?= csrf_field() ?>

        <div class="rg-field">
            <label class="rg-label" for="commune">Commune</label>
            <select id="commune" name="idcommune" class="rg-select" required
                    data-commune-department data-department-target="#departement-display" autofocus>
                <?= commune_select_options($communes, $selectedCommune) ?>
            </select>
          
        </div>

        <div class="rg-field">
            <label class="rg-label" for="departement-display">Département</label>
            <input type="text" id="departement-display" class="rg-input" readonly
                   value="<?= e($departementNom) ?>" placeholder="— Automatique —">
        </div>

        <div class="rg-field">
            <label class="rg-label" for="nom">Nom de l'arrondissement</label>
            <input type="text" id="nom" name="nom" class="rg-input"
                   value="<?= e(old('nom', $arrondissement['nom'] ?? '')) ?>"
                   placeholder="Ex. Akpakpa" required minlength="2" maxlength="250">
        </div>

        <div class="rg-form-actions">
            <button type="submit" class="rg-btn rg-btn--primary">
                <?= icon('save') ?><span><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter l\'arrondissement' ?></span>
            </button>
            <a href="<?= e($backUrl) ?>" class="rg-btn rg-btn--ghost">Annuler</a>
        </div>
    </form>
</div>
</div>
