<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<string, mixed>|null $registre */
/** @var array<int, array<string, mixed>> $villes */
/** @var int $selectedVille */
/** @var string $formAction */
/** @var string $mode */

$isEdit       = $mode === 'edit';
$selectedData = null;

foreach ($villes as $ville) {
    if ((int) $ville['idville'] === $selectedVille) {
        $selectedData = $ville;
        break;
    }
}

$capacity   = $selectedData !== null ? (int) ($selectedData['population'] ?? 0) : 0;
$registered = $selectedData !== null ? (int) ($selectedData['nb_registres'] ?? 0) : 0;
$remaining  = max(0, $capacity - $registered);
$backUrl    = url('registres');
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2><?= $isEdit ? 'Modifier le profil' : 'Nouveau profil' ?></h2>
        <p class="rg-muted">Enregistrer un nouveau citoyen.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= e($backUrl) ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Retour</span></a>
    </div>
</div>

<div class="rg-card rg-card--form">
    <form method="post" action="<?= e($formAction) ?>" class="rg-form">
        <?= csrf_field() ?>

        <div class="rg-form-grid">
            <div class="rg-field">
                <label class="rg-label" for="nom">Nom</label>
                <input type="text" id="nom" name="nom" class="rg-input"
                       value="<?= e(old('nom', $registre['nom'] ?? '')) ?>" placeholder="Ex. DOSSOU"
                       required minlength="2" maxlength="255" autofocus>
            </div>

            <div class="rg-field">
                <label class="rg-label" for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" class="rg-input"
                       value="<?= e(old('prenom', $registre['prenom'] ?? '')) ?>" placeholder="Ex. Boris"
                       required minlength="2" maxlength="255">
            </div>

            <div class="rg-field">
                <label class="rg-label" for="date_ref">Date de naissance</label>
                <input type="date" id="date_ref" name="date_ref" class="rg-input"
                       value="<?= e(old('date_ref', $registre['date_ref'] ?? '')) ?>"
                       min="1900-01-01" max="<?= e(date('Y-m-d')) ?>" required>

            </div>

            <div class="rg-field">
                <label class="rg-label" for="genre">Sexe</label>
                <?php $selectedGenre = (string) old('genre', $registre['genre'] ?? ''); ?>
                <select id="genre" name="genre" class="rg-select" required>
                    <option value="">— Choisir —</option>
                    <option value="M" <?= $selectedGenre === 'M' ? 'selected' : '' ?>>Masculin (M)</option>
                    <option value="F" <?= $selectedGenre === 'F' ? 'selected' : '' ?>>Féminin (F)</option>
                </select>
            </div>

            <div class="rg-field rg-field--full">
                <label class="rg-label" for="idville">Ville</label>
                <select id="idville" name="idville" class="rg-select" required
                        data-capacity-source data-capacity-target="#capacity-hint">
                    <?= ville_select_options($villes, $selectedVille) ?>
                </select>
                <div class="rg-capacity-hint" id="capacity-hint" data-capacity-hint>
                    <?php if ($selectedData === null): ?>
                        Sélectionnez une ville pour voir sa capacité.
                    <?php else: ?>
                        Capacité : <?= e(format_number($capacity)) ?> ·
                        Inscrits : <?= e(format_number($registered)) ?> ·
                        Reste : <?= e(format_number($remaining)) ?>
                    <?php endif; ?>
                </div>
           
            </div>
        </div>

        <div class="rg-form-actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('save') ?><span><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter le profil' ?></span></button>
            <a href="<?= e($backUrl) ?>" class="rg-btn rg-btn--ghost">Annuler</a>
        </div>
    </form>
</div>
