<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var int $nombre */
/** @var array<int, array<string, mixed>> $departements */
/** @var array<int, array<string, mixed>> $oldItems */
/** @var int $startStep */
?>
<div class="rg-form-page">
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Saisie multiple de communes</h2>
        <p class="rg-muted">Ajoutez plusieurs communes en une seule opération.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('communes') ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Retour</span></a>
    </div>
</div>

<?php if ($nombre < 2): ?>
    <div class="rg-card rg-card--form rg-card--narrow">
        <form method="get" action="<?= url('communes/creer-multiple') ?>" class="rg-form">
            <?= route_field('communes/creer-multiple') ?>

            <div class="rg-field">
                <label class="rg-label" for="nombre">Combien de communes souhaitez-vous ajouter ?</label>
                <input type="number" id="nombre" name="nombre" class="rg-input"
                       min="2" max="50" step="1" value="<?= e(old('nombre', 5)) ?>" required autofocus>
                <p class="rg-hint">
                    Entre 2 et 50 communes. Les fiches s'affichent l'une après l'autre :
                    remplissez la première, cliquez sur « Suivant », et ainsi de suite.
                    Toutes les communes sont enregistrées d'un seul coup à la fin.
                </p>
            </div>

            <div class="rg-form-actions">
                <button type="submit" class="rg-btn rg-btn--primary"><?= icon('chevron-right') ?><span>Continuer</span></button>
                <a href="<?= url('communes') ?>" class="rg-btn rg-btn--ghost">Annuler</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="rg-card">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('layers') ?><span>Saisie guidée</span></h3>
            <span class="rg-badge rg-badge--neutral"><?= (int) $nombre ?> communes</span>
        </div>

        <div class="rg-card-body">
            <form method="post" action="<?= url('communes/multiple') ?>"
                  class="rg-form rg-multi" data-multi-form
                  data-start-step="<?= (int) $startStep ?>" data-total-steps="<?= (int) $nombre ?>">
                <?= csrf_field() ?>

                <div class="rg-multi-head" data-multi-head>
                    <div class="rg-progress"><span class="rg-progress-bar" data-multi-progress></span></div>
                    <span class="rg-badge rg-badge--primary" data-multi-counter>Étape 1 / <?= (int) $nombre ?></span>
                </div>

                <?php for ($i = 0; $i < $nombre; $i++): ?>
                    <?php
                    $oldNom = (string) ($oldItems[$i]['nom'] ?? '');
                    $oldDep = (int) ($oldItems[$i]['iddepartement'] ?? 0);
                    ?>
                    <fieldset class="rg-step" data-step>
                        <legend class="rg-step-legend">
                            <span class="rg-step-number"><?= $i + 1 ?></span>
                            Commune <?= $i + 1 ?> sur <?= (int) $nombre ?>
                        </legend>

                        <div class="rg-form-grid">
                            <div class="rg-field">
                                <label class="rg-label" for="commune-nom-<?= $i ?>">Nom de la commune</label>
                                <input type="text" id="commune-nom-<?= $i ?>" name="communes[<?= $i ?>][nom]"
                                       class="rg-input" value="<?= e($oldNom) ?>" placeholder="Ex. Cotonou"
                                       required minlength="2" maxlength="250" <?= $i === $startStep ? 'autofocus' : '' ?>>
                            </div>

                            <div class="rg-field">
                                <label class="rg-label" for="commune-dep-<?= $i ?>">Département</label>
                                <select id="commune-dep-<?= $i ?>" name="communes[<?= $i ?>][iddepartement]"
                                        class="rg-select" required>
                                    <option value="">— Choisir un département —</option>
                                    <?php foreach ($departements as $departement): ?>
                                        <option value="<?= (int) $departement['iddepartement'] ?>" <?= $oldDep === (int) $departement['iddepartement'] ? 'selected' : '' ?>>
                                            <?= e($departement['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                <?php endfor; ?>

                <div class="rg-form-actions">
                    <button type="button" class="rg-btn rg-btn--ghost rg-wizard-only" data-multi-prev>
                        <?= icon('chevron-left') ?><span>Précédent</span>
                    </button>
                    <button type="submit" class="rg-btn rg-btn--primary" data-multi-submit>
                        <?= icon('save') ?><span>Enregistrer les <?= (int) $nombre ?> communes</span>
                    </button>
                    <button type="button" class="rg-btn rg-btn--primary rg-wizard-only" data-multi-next>
                        <span>Suivant</span><?= icon('chevron-right') ?>
                    </button>
                    <a href="<?= url('communes') ?>" class="rg-btn rg-btn--ghost">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
