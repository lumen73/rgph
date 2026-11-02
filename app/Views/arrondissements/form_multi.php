<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var int $nombre */
/** @var array<int, array<string, mixed>> $communes */
/** @var array<int, array<string, mixed>> $oldItems */
/** @var int $startStep */
?>
<div class="rg-form-page">
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Saisie multiple d'arrondissements</h2>
        <p class="rg-muted">Ajoutez plusieurs arrondissements en une seule opération.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('arrondissements') ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Retour</span></a>
    </div>
</div>

<?php if ($nombre < 2): ?>
    <div class="rg-card rg-card--form rg-card--narrow">
        <form method="get" action="<?= url('arrondissements/creer-multiple') ?>" class="rg-form">
            <?= route_field('arrondissements/creer-multiple') ?>

            <div class="rg-field">
                <label class="rg-label" for="nombre">Combien d'arrondissements souhaitez-vous ajouter ?</label>
                <input type="number" id="nombre" name="nombre" class="rg-input"
                       min="2" max="50" step="1" value="<?= e(old('nombre', 5)) ?>" required autofocus>
                <p class="rg-hint">
                    Entre 2 et 50 arrondissements. Les fiches s'affichent l'une après l'autre :
                    remplissez la première, cliquez sur « Suivant », et ainsi de suite.
                    Tous les arrondissements sont enregistrés d'un seul coup à la fin.
                </p>
            </div>

            <div class="rg-form-actions">
                <button type="submit" class="rg-btn rg-btn--primary"><?= icon('chevron-right') ?><span>Continuer</span></button>
                <a href="<?= url('arrondissements') ?>" class="rg-btn rg-btn--ghost">Annuler</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="rg-card">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('map') ?><span>Saisie guidée</span></h3>
            <span class="rg-badge rg-badge--neutral"><?= (int) $nombre ?> arrondissements</span>
        </div>

        <div class="rg-card-body">
            <form method="post" action="<?= url('arrondissements/multiple') ?>"
                  class="rg-form rg-multi" data-multi-form
                  data-start-step="<?= (int) $startStep ?>" data-total-steps="<?= (int) $nombre ?>">
                <?= csrf_field() ?>

                <div class="rg-multi-head" data-multi-head>
                    <div class="rg-progress"><span class="rg-progress-bar" data-multi-progress></span></div>
                    <span class="rg-badge rg-badge--primary" data-multi-counter>Étape 1 / <?= (int) $nombre ?></span>
                </div>

                <?php for ($i = 0; $i < $nombre; $i++): ?>
                    <?php
                    $oldNom       = (string) ($oldItems[$i]['nom'] ?? '');
                    $oldCom       = (int) ($oldItems[$i]['idcommune'] ?? 0);
                    $departement  = commune_department_name($communes, $oldCom);
                    $departmentId = 'arrondissement-dep-' . $i;
                    ?>
                    <fieldset class="rg-step" data-step>
                        <legend class="rg-step-legend">
                            <span class="rg-step-number"><?= $i + 1 ?></span>
                            Arrondissement <?= $i + 1 ?> sur <?= (int) $nombre ?>
                        </legend>

                        <div class="rg-form-grid">
                            <div class="rg-field">
                                <label class="rg-label" for="arrondissement-nom-<?= $i ?>">Nom de l'arrondissement</label>
                                <input type="text" id="arrondissement-nom-<?= $i ?>" name="arrondissements[<?= $i ?>][nom]"
                                       class="rg-input" value="<?= e($oldNom) ?>" placeholder="Ex. Akpakpa"
                                       required minlength="2" maxlength="250" <?= $i === $startStep ? 'autofocus' : '' ?>>
                            </div>

                            <div class="rg-field">
                                <label class="rg-label" for="arrondissement-commune-<?= $i ?>">Commune</label>
                                <select id="arrondissement-commune-<?= $i ?>" name="arrondissements[<?= $i ?>][idcommune]"
                                        class="rg-select" required
                                        data-commune-department data-department-target="#<?= $departmentId ?>">
                                    <?= commune_select_options($communes, $oldCom) ?>
                                </select>
                            </div>

                            <div class="rg-field rg-field--full">
                                <label class="rg-label" for="<?= $departmentId ?>">Département</label>
                                <input type="text" id="<?= $departmentId ?>" class="rg-input" readonly
                                       value="<?= e($departement) ?>" placeholder="— Automatique —">
                                <p class="rg-hint">Renseigné automatiquement à partir de la commune.</p>
                            </div>
                        </div>
                    </fieldset>
                <?php endfor; ?>

                <div class="rg-form-actions">
                    <button type="button" class="rg-btn rg-btn--ghost rg-wizard-only" data-multi-prev>
                        <?= icon('chevron-left') ?><span>Précédent</span>
                    </button>
                    <button type="submit" class="rg-btn rg-btn--primary" data-multi-submit>
                        <?= icon('save') ?><span>Enregistrer les <?= (int) $nombre ?> arrondissements</span>
                    </button>
                    <button type="button" class="rg-btn rg-btn--primary rg-wizard-only" data-multi-next>
                        <span>Suivant</span><?= icon('chevron-right') ?>
                    </button>
                    <a href="<?= url('arrondissements') ?>" class="rg-btn rg-btn--ghost">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
