<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var int $nombre */
/** @var array<int, array<string, mixed>> $arrondissements */
/** @var array<int, array<string, mixed>> $communes */
/** @var array<int, array<string, mixed>> $oldItems */
/** @var int $startStep */
?>
<div class="rg-form-page">
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Saisie multiple de villes</h2>
        <p class="rg-muted">Ajoutez plusieurs villes en une seule opération, avec leur arrondissement.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('villes') ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Retour</span></a>
    </div>
</div>

<?php if ($nombre < 2): ?>
    <div class="rg-card rg-card--form rg-card--narrow">
        <form method="get" action="<?= url('villes/creer-multiple') ?>" class="rg-form">
            <?= route_field('villes/creer-multiple') ?>

            <div class="rg-field">
                <label class="rg-label" for="nombre">Combien de villes souhaitez-vous ajouter ?</label>
                <input type="number" id="nombre" name="nombre" class="rg-input"
                       min="2" max="50" step="1" value="<?= e(old('nombre', 5)) ?>" required autofocus>
                <p class="rg-hint">
                    Entre 2 et 50 villes. Les fiches s'affichent l'une après l'autre :
                    remplissez la première, cliquez sur « Suivant », et ainsi de suite.
                    Toutes les villes sont enregistrées d'un seul coup à la fin.
                </p>
            </div>

            <div class="rg-form-actions">
                <button type="submit" class="rg-btn rg-btn--primary"><?= icon('chevron-right') ?><span>Continuer</span></button>
                <a href="<?= url('villes') ?>" class="rg-btn rg-btn--ghost">Annuler</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="rg-card">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('map-pin') ?><span>Saisie guidée</span></h3>
            <span class="rg-badge rg-badge--neutral"><?= (int) $nombre ?> villes</span>
        </div>

        <div class="rg-card-body">
            <form method="post" action="<?= url('villes/multiple') ?>"
                  class="rg-form rg-multi" data-multi-form
                  data-start-step="<?= (int) $startStep ?>" data-total-steps="<?= (int) $nombre ?>">
                <?= csrf_field() ?>

                <div class="rg-multi-head" data-multi-head>
                    <div class="rg-progress"><span class="rg-progress-bar" data-multi-progress></span></div>
                    <span class="rg-badge rg-badge--primary" data-multi-counter>Étape 1 / <?= (int) $nombre ?></span>
                </div>

                <?php for ($i = 0; $i < $nombre; $i++): ?>
                    <?php
                    $oldNom        = (string) ($oldItems[$i]['nom'] ?? '');
                    $oldPopulation = (string) ($oldItems[$i]['population'] ?? '');
                    $oldArr        = (int) ($oldItems[$i]['idarrondissement'] ?? 0);
                    $oldCom        = (int) ($oldItems[$i]['idcommune'] ?? 0);

                    if ($oldCom <= 0 && $oldArr > 0) {
                        foreach ($arrondissements as $arrondissement) {
                            if ((int) $arrondissement['idarrondissement'] === $oldArr) {
                                $oldCom = (int) $arrondissement['idcommune'];
                                break;
                            }
                        }
                    }

                    $departement = commune_department_name($communes, $oldCom);
                    $communeId   = 'ville-commune-' . $i;
                    $departmentId = 'ville-dep-' . $i;
                    ?>
                    <fieldset class="rg-step" data-step>
                        <legend class="rg-step-legend">
                            <span class="rg-step-number"><?= $i + 1 ?></span>
                            Ville <?= $i + 1 ?> sur <?= (int) $nombre ?>
                        </legend>

                        <div class="rg-form-grid">
                            <div class="rg-field">
                                <label class="rg-label" for="ville-nom-<?= $i ?>">Nom de la ville</label>
                                <input type="text" id="ville-nom-<?= $i ?>" name="villes[<?= $i ?>][nom]"
                                       class="rg-input" value="<?= e($oldNom) ?>" placeholder="Ex. Cotonou"
                                       required minlength="2" maxlength="250" <?= $i === $startStep ? 'autofocus' : '' ?>>
                            </div>

                            <div class="rg-field">
                                <label class="rg-label" for="ville-population-<?= $i ?>">Population</label>
                                <input type="number" id="ville-population-<?= $i ?>" name="villes[<?= $i ?>][population]"
                                       class="rg-input" value="<?= e($oldPopulation) ?>" placeholder="Ex. 12000"
                                       min="0" step="1" required>
                            </div>

                            <div class="rg-field rg-field--full">
                                <label class="rg-label" for="ville-arr-<?= $i ?>">Arrondissement</label>
                                <select id="ville-arr-<?= $i ?>" name="villes[<?= $i ?>][idarrondissement]"
                                        class="rg-select"
                                        data-arrondissement-location
                                        data-commune-target="#<?= $communeId ?>"
                                        data-department-target="#<?= $departmentId ?>">
                                    <?= arrondissement_select_options($arrondissements, $oldArr) ?>
                                </select>
                               
                            </div>

                            <div class="rg-field">
                                <label class="rg-label" for="<?= $communeId ?>">Commune</label>
                                <select id="<?= $communeId ?>" name="villes[<?= $i ?>][idcommune]"
                                        class="rg-select" required
                                        data-commune-department data-department-target="#<?= $departmentId ?>">
                                    <?= commune_select_options($communes, $oldCom) ?>
                                </select>
                            </div>

                            <div class="rg-field">
                                <label class="rg-label" for="<?= $departmentId ?>">Département</label>
                                <input type="text" id="<?= $departmentId ?>" class="rg-input" readonly
                                       value="<?= e($departement) ?>" placeholder="— Automatique —">
                            </div>
                        </div>
                    </fieldset>
                <?php endfor; ?>

                <div class="rg-form-actions">
                    <button type="button" class="rg-btn rg-btn--ghost rg-wizard-only" data-multi-prev>
                        <?= icon('chevron-left') ?><span>Précédent</span>
                    </button>
                    <button type="submit" class="rg-btn rg-btn--primary" data-multi-submit>
                        <?= icon('save') ?><span>Enregistrer les <?= (int) $nombre ?> villes</span>
                    </button>
                    <button type="button" class="rg-btn rg-btn--primary rg-wizard-only" data-multi-next>
                        <span>Suivant</span><?= icon('chevron-right') ?>
                    </button>
                    <a href="<?= url('villes') ?>" class="rg-btn rg-btn--ghost">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
