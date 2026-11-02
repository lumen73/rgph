<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<string, mixed>|null $ville */
/** @var array<int, array<string, mixed>> $arrondissements */
/** @var array<int, array<string, mixed>> $communes */
/** @var string $formAction */
/** @var string $mode */

$isEdit                 = $mode === 'edit';
$selectedArrondissement = (int) old('idarrondissement', $ville['idarrondissement'] ?? 0);
$selectedCommune        = (int) old('idcommune', $ville['idcommune'] ?? 0);

if ($selectedCommune <= 0 && $selectedArrondissement > 0) {
    foreach ($arrondissements as $arrondissement) {
        if ((int) $arrondissement['idarrondissement'] === $selectedArrondissement) {
            $selectedCommune = (int) $arrondissement['idcommune'];
            break;
        }
    }
}

$departementNom = commune_department_name($communes, $selectedCommune);
$backUrl        = $isEdit && $ville !== null ? url('villes/' . (int) $ville['idville']) : url('villes');
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2><?= $isEdit ? 'Modifier la ville' : 'Nouvelle ville' ?></h2>
        <p class="rg-muted"><?= $isEdit ? 'Mettez à jour les informations de la ville.' : 'Renseignez le rattachement administratif et la population.' ?></p>
    </div>
    <div class="rg-actions">
        <a href="<?= e($backUrl) ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Retour</span></a>
    </div>
</div>

<div class="rg-card rg-card--form">
    <form method="post" action="<?= e($formAction) ?>" class="rg-form">
        <?= csrf_field() ?>

        <div class="rg-form-grid">
            <div class="rg-field rg-field--full">
                <label class="rg-label" for="idarrondissement">Arrondissement</label>
                <select id="idarrondissement" name="idarrondissement" class="rg-select"
                        data-arrondissement-location
                        data-commune-target="#idcommune"
                        data-department-target="#departement-display">
                    <?= arrondissement_select_options($arrondissements, $selectedArrondissement) ?>
                </select>
               
            </div>

            <div class="rg-field">
                <label class="rg-label" for="idcommune">Commune</label>
                <select id="idcommune" name="idcommune" class="rg-select" required
                        data-commune-department data-department-target="#departement-display">
                    <?= commune_select_options($communes, $selectedCommune) ?>
                </select>
            </div>

            <div class="rg-field">
                <label class="rg-label" for="departement-display">Département</label>
                <input type="text" id="departement-display" class="rg-input" readonly
                       value="<?= e($departementNom) ?>" placeholder="— Automatique —">
                
            </div>

            <div class="rg-field">
                <label class="rg-label" for="nom">Nom de la ville</label>
                <input type="text" id="nom" name="nom" class="rg-input"
                       value="<?= e(old('nom', $ville['nom'] ?? '')) ?>" placeholder="Ex. Cotonou" required maxlength="250">
            </div>

            <div class="rg-field">
                <label class="rg-label" for="population">Nombres d'habitants</label>
                <input type="number" id="population" name="population" class="rg-input"
                       value="<?= e(old('population', $ville['population'] ?? '')) ?>" placeholder="Ex. 679012" min="0" step="1" required>
 
            </div>
        </div>

        <div class="rg-form-actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('save') ?><span><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter la ville' ?></span></button>
            <a href="<?= e($backUrl) ?>" class="rg-btn rg-btn--ghost">Annuler</a>
        </div>
    </form>
</div>
