<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<int, array<string, mixed>> $rows */

$total = 0;
$maxPopulation = 1;
foreach ($rows as $row) {
    $total += (int) $row['population'];
    $maxPopulation = max($maxPopulation, (int) $row['population']);
}
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Population par département</h2>
        <p class="rg-muted">Répartition de la population totale selon le découpage administratif.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('rapports') ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Rapports</span></a>
        <?php if (can('export')): ?>
            <a href="<?= url('export/departements') ?>" class="rg-btn rg-btn--primary" download><?= icon('download') ?><span>Export CSV</span></a>
        <?php endif; ?>
    </div>
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
                            <th>Département</th>
                            <th class="rg-num">Communes</th>
                            <th class="rg-num">Villes</th>
                            <th class="rg-num">Population</th>
                            <th>Répartition</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="rg-strong"><?= e($row['nom']) ?></td>
                                <td class="rg-num"><?= e(format_number($row['nb_communes'])) ?></td>
                                <td class="rg-num"><?= e(format_number($row['nb_villes'])) ?></td>
                                <td class="rg-num rg-strong"><?= e(format_number($row['population'])) ?></td>
                                <td class="rg-bar-cell">
                                    <span class="rg-bar-track rg-bar-track--sm">
                                        <span class="rg-bar-fill" style="width: <?= (int) round(((int) $row['population'] / $maxPopulation) * 100) ?>%"></span>
                                    </span>
                                    <span class="rg-muted rg-small"><?= $total > 0 ? (int) round(((int) $row['population'] / $total) * 100) : 0 ?> %</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="rg-strong">Total</td>
                            <td colspan="2"></td>
                            <td class="rg-num rg-strong"><?= e(format_number($total)) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>