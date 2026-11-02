<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<int, array<string, mixed>> $rows */
/** @var int $limit */

$maxPopulation = 1;
foreach ($rows as $row) {
    $maxPopulation = max($maxPopulation, (int) $row['population']);
}
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Population par commune</h2>
        <p class="rg-muted">Classement des communes les plus peuplées.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('rapports') ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Rapports</span></a>
        <?php if (can('export')): ?>
            <a href="<?= url('export/communes') ?>" class="rg-btn rg-btn--primary" download><?= icon('download') ?><span>Export CSV</span></a>
        <?php endif; ?>
    </div>
</div>

<div class="rg-card">
    <form method="get" action="<?= url('rapports/communes') ?>" class="rg-filters">
        <?= route_field('rapports/communes') ?>
        <div class="rg-filter">
            <label class="rg-label" for="limite">Nombre de communes affichées</label>
            <select id="limite" name="limite" class="rg-select">
                <?php foreach ([10, 20, 50, 100] as $option): ?>
                    <option value="<?= $option ?>" <?= $limit === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rg-filter rg-filter--actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('filter') ?><span>Afficher</span></button>
        </div>
    </form>
</div>

<div class="rg-card">
    <div class="rg-card-body rg-p0">
        <?php if ($rows === []): ?>
            <div class="rg-empty"><p>Aucune donnée disponible.</p></div>
        <?php else: ?>
            <div class="rg-table-wrap">
                <table class="rg-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Commune</th>
                            <th>Département</th>
                            <th class="rg-num">Villes</th>
                            <th class="rg-num">Population</th>
                            <th>Répartition</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td class="rg-muted"><?= e($index + 1) ?></td>
                                <td class="rg-strong"><?= e($row['nom']) ?></td>
                                <td><?= e($row['departement']) ?></td>
                                <td class="rg-num"><?= e(format_number($row['nb_villes'])) ?></td>
                                <td class="rg-num rg-strong"><?= e(format_number($row['population'])) ?></td>
                                <td class="rg-bar-cell">
                                    <span class="rg-bar-track rg-bar-track--sm">
                                        <span class="rg-bar-fill" style="width: <?= (int) round(((int) $row['population'] / $maxPopulation) * 100) ?>%"></span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>