<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<int, array<string, mixed>> $parDepartement */
/** @var array<int, array<string, mixed>> $topVilles */
/** @var array<int, array<string, mixed>> $villesRecentes */

$maxPopulation = 1;
foreach ($parDepartement as $departement) {
    $maxPopulation = max($maxPopulation, (int) $departement['population']);
}
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Vue d'ensemble</h2>
        <p class="rg-muted">Synthèse du recensement : population, découpage administratif et indicateurs.</p>
    </div>
    <div class="rg-actions">
        <?php if (can('ville.create')): ?>
            <a href="<?= url('villes/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Nouvelle ville</span></a>
        <?php endif; ?>
        <?php if (can('export')): ?>
            <a href="<?= url('export/villes') ?>" class="rg-btn rg-btn--ghost"><?= icon('download') ?><span>Export CSV</span></a>
        <?php endif; ?>
        <?php if (can('registre.view')): ?>
            <a href="<?= url('registres') ?>" class="rg-btn rg-btn--ghost"><?= icon('users') ?><span>Registre</span></a>
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
            <span class="rg-stat-label">Villes recensées</span>
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
            <a href="<?= url('rapports/departements') ?>" class="rg-link">Détails <?= icon('chevron-right', 'ico ico--sm') ?></a>
        </div>
        <div class="rg-card-body">
            <?php if ($parDepartement === []): ?>
                <p class="rg-empty">Aucune donnée disponible.</p>
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
            <h3 class="rg-card-title"><?= icon('trending') ?><span>Villes les plus peuplées</span></h3>
            <a href="<?= url('rapports/villes') ?>" class="rg-link">Détails <?= icon('chevron-right', 'ico ico--sm') ?></a>
        </div>
        <div class="rg-card-body rg-p0">
            <?php if ($topVilles === []): ?>
                <p class="rg-empty">Aucune ville enregistrée.</p>
            <?php else: ?>
                <table class="rg-table">
                    <thead>
                        <tr><th>Ville</th><th>Localisation</th><th class="rg-num">Population</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topVilles as $ville): ?>
                            <tr>
                                <td><a href="<?= url('villes/' . (int) $ville['idville']) ?>" class="rg-link"><?= e($ville['nom']) ?></a></td>
                                <td class="rg-muted"><?= e($ville['commune'] . ' · ' . $ville['departement']) ?></td>
                                <td class="rg-num rg-strong"><?= e(format_number($ville['population'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>
</div>

<section class="rg-card">
    <div class="rg-card-head">
        <h3 class="rg-card-title"><?= icon('plus') ?><span>Dernières villes ajoutées</span></h3>
        <a href="<?= url('villes') ?>" class="rg-link">Toutes les villes <?= icon('chevron-right', 'ico ico--sm') ?></a>
    </div>
    <div class="rg-card-body rg-p0">
        <?php if ($villesRecentes === []): ?>
            <div class="rg-empty">
                <p>Aucune ville n'a encore été enregistrée.</p>
                <?php if (can('ville.create')): ?>
                    <a href="<?= url('villes/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Ajouter une ville</span></a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="rg-table">
                <thead>
                    <tr><th>Ville</th><th>Commune</th><th>Département</th><th class="rg-num">Population</th><th class="rg-num"></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($villesRecentes as $ville): ?>
                        <tr>
                            <td class="rg-strong"><?= e($ville['nom']) ?></td>
                            <td><?= e($ville['commune']) ?></td>
                            <td><?= e($ville['departement']) ?></td>
                            <td class="rg-num"><?= e(format_number($ville['population'])) ?></td>
                            <td class="rg-num">
                                <a href="<?= url('villes/' . (int) $ville['idville']) ?>" class="rg-icon-btn rg-icon-btn--sm" title="Consulter"><?= icon('chevron-right', 'ico ico--sm') ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<div class="rg-section-head">
    <div class="rg-page-head-titles">
        <h3>Profils du registre</h3>
        <p class="rg-muted">Répartition des profils de type Homme, Femme et Enfants pour chaque entité.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('rapports/registre') ?>" class="rg-btn rg-btn--ghost"><?= icon('chart') ?><span>Rapport détaillé</span></a>
        <?php if (can('registre.create')): ?>
            <a href="<?= url('registres/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Nouveau profil</span></a>
        <?php endif; ?>
    </div>
</div>

<?php include VIEW_PATH . '/partials/registre_tables.php'; ?>