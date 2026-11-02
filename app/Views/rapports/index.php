<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<int, array<string, mixed>> $parDepartement */
/** @var array<int, array<string, mixed>> $topVilles */

$maxPopulation = 1;
foreach ($parDepartement as $departement) {
    $maxPopulation = max($maxPopulation, (int) $departement['population']);
}
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Rapports</h2>
        <p class="rg-muted">Analyses statistiques de la population recensée.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('rapports/registre') ?>" class="rg-btn rg-btn--ghost"><?= icon('users') ?><span>Profils du registre</span></a>
        <?php if (can('export')): ?>
            <a href="<?= url('export/villes') ?>" class="rg-btn rg-btn--ghost" download><?= icon('download') ?><span>Export villes</span></a>
            <a href="<?= url('export/departements') ?>" class="rg-btn rg-btn--ghost" download><?= icon('download') ?><span>Export départements</span></a>
        <?php endif; ?>
    </div>
</div>

<div class="rg-stats">
    <div class="rg-stat rg-stat--primary">
        <span class="rg-stat-ico"><?= icon('trending', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($population)) ?></span>
            <span class="rg-stat-label">Population totale</span>
        </div>
    </div>
    <div class="rg-stat rg-stat--success">
        <span class="rg-stat-ico"><?= icon('map-pin', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($nbVilles)) ?></span>
            <span class="rg-stat-label">Villes</span>
        </div>
    </div>
    <div class="rg-stat rg-stat--info">
        <span class="rg-stat-ico"><?= icon('building', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($nbCommunes)) ?></span>
            <span class="rg-stat-label">Communes</span>
        </div>
    </div>
    <div class="rg-stat rg-stat--warn">
        <span class="rg-stat-ico"><?= icon('layers', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($nbDepartements)) ?></span>
            <span class="rg-stat-label">Départements</span>
        </div>
    </div>
</div>

<div class="rg-grid-2">
    <section class="rg-card">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('chart') ?><span>Population par département</span></h3>
            <a href="<?= url('rapports/departements') ?>" class="rg-link">Voir <?= icon('chevron-right', 'ico ico--sm') ?></a>
        </div>
        <div class="rg-card-body">
            <?php if ($parDepartement === []): ?>
                <p class="rg-empty">Aucune donnée.</p>
            <?php else: ?>
                <div class="rg-bars">
                    <?php foreach ($parDepartement as $departement): ?>
                        <div class="rg-bar-row">
                            <span class="rg-bar-name" title="<?= e($departement['nom']) ?>"><?= e($departement['nom']) ?></span>
                            <span class="rg-bar-track">
                                <span class="rg-bar-fill" style="width: <?= (int) round(((int) $departement['population'] / $maxPopulation) * 100) ?>%"></span>
                            </span>
                            <span class="rg-bar-value"><?= e(format_number($departement['population'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="rg-card">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('trending') ?><span>Top 10 des villes</span></h3>
            <a href="<?= url('rapports/villes') ?>" class="rg-link">Voir <?= icon('chevron-right', 'ico ico--sm') ?></a>
        </div>
        <div class="rg-card-body rg-p0">
            <?php if ($topVilles === []): ?>
                <p class="rg-empty">Aucune ville.</p>
            <?php else: ?>
                <table class="rg-table">
                    <thead>
                        <tr><th>#</th><th>Ville</th><th class="rg-num">Population</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topVilles as $index => $ville): ?>
                            <tr>
                                <td class="rg-muted"><?= e($index + 1) ?></td>
                                <td>
                                    <a href="<?= url('villes/' . (int) $ville['idville']) ?>" class="rg-link rg-strong"><?= e($ville['nom']) ?></a>
                                    <div class="rg-muted rg-small"><?= e($ville['commune'] . ' · ' . $ville['departement']) ?></div>
                                </td>
                                <td class="rg-num rg-strong"><?= e(format_number($ville['population'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="rg-reports-grid">
    <a href="<?= url('rapports/departements') ?>" class="rg-report-link">
        <?= icon('layers', 'ico ico--lg') ?>
        <div><strong>Par département</strong><small>Population agrégée par département</small></div>
        <?= icon('chevron-right', 'ico ico--sm') ?>
    </a>
    <a href="<?= url('rapports/communes') ?>" class="rg-report-link">
        <?= icon('building', 'ico ico--lg') ?>
        <div><strong>Par commune</strong><small>Classement des communes les plus peuplées</small></div>
        <?= icon('chevron-right', 'ico ico--sm') ?>
    </a>
    <a href="<?= url('rapports/villes') ?>" class="rg-report-link">
        <?= icon('map-pin', 'ico ico--lg') ?>
        <div><strong>Par ville</strong><small>Villes les plus peuplées</small></div>
        <?= icon('chevron-right', 'ico ico--sm') ?>
    </a>
    <a href="<?= url('rapports/registre') ?>" class="rg-report-link">
        <?= icon('users', 'ico ico--lg') ?>
        <div><strong>Profils du registre</strong><small>Homme, Femme et Enfants par entité</small></div>
        <?= icon('chevron-right', 'ico ico--sm') ?>
    </a>
</div>

<?php if (can('export')): ?>
    <section class="rg-card" id="exports">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('download') ?><span>Exporter les données</span></h3>
            <span class="rg-muted rg-small">Fichiers CSV compatibles Excel / LibreOffice</span>
        </div>
        <div class="rg-card-body">
            <div class="rg-export-grid">
                <a href="<?= url('export/villes') ?>" class="rg-export-link" download>
                    <span class="rg-export-ico"><?= icon('map-pin') ?></span>
                    <span class="rg-export-text"><strong>Villes</strong><small>Département, commune, population</small></span>
                    <?= icon('download', 'ico ico--sm') ?>
                </a>
                <a href="<?= url('export/communes') ?>" class="rg-export-link" download>
                    <span class="rg-export-ico"><?= icon('building') ?></span>
                    <span class="rg-export-text"><strong>Communes</strong><small>Arrondissements, villes, population</small></span>
                    <?= icon('download', 'ico ico--sm') ?>
                </a>
                <a href="<?= url('export/arrondissements') ?>" class="rg-export-link" download>
                    <span class="rg-export-ico"><?= icon('map') ?></span>
                    <span class="rg-export-text"><strong>Arrondissements</strong><small>Département, commune, arrondissement</small></span>
                    <?= icon('download', 'ico ico--sm') ?>
                </a>
                <a href="<?= url('export/departements') ?>" class="rg-export-link" download>
                    <span class="rg-export-ico"><?= icon('layers') ?></span>
                    <span class="rg-export-text"><strong>Départements</strong><small>Communes, villes, population</small></span>
                    <?= icon('download', 'ico ico--sm') ?>
                </a>
                <a href="<?= url('export/registres') ?>" class="rg-export-link" download>
                    <span class="rg-export-ico"><?= icon('users') ?></span>
                    <span class="rg-export-text"><strong>Registre</strong><small>Profils, dates de naissance, genres et localisation</small></span>
                    <?= icon('download', 'ico ico--sm') ?>
                </a>
            </div>
        </div>
    </section>
<?php endif; ?>