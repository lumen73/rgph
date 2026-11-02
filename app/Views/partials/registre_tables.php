<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<string, int> $registreStats */
/** @var array<int, array<string, mixed>> $registreParVille */
/** @var array<int, array<string, mixed>> $registreParArrondissement */
/** @var array<int, array<string, mixed>> $registreParCommune */
/** @var array<int, array<string, mixed>> $registreParDepartement */
/** @var array<string, mixed> $registreSansArrondissement */

$totalVilles = count($registreParVille);
$totalArrondissements = count($registreParArrondissement);
$totalCommunes = count($registreParCommune);
$totalDepartements = count($registreParDepartement);
?>
<div class="rg-stats">
    <div class="rg-stat rg-stat--primary">
        <span class="rg-stat-ico"><?= icon('users', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($registreStats['total'])) ?></span>
            <span class="rg-stat-label">Profils enregistrés</span>
        </div>
    </div>
    <div class="rg-stat rg-stat--info">
        <span class="rg-stat-ico"><?= icon('user', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($registreStats['hommes'])) ?></span>
            <span class="rg-stat-label">Homme</span>
        </div>
    </div>
    <div class="rg-stat rg-stat--warn">
        <span class="rg-stat-ico"><?= icon('user', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($registreStats['femmes'])) ?></span>
            <span class="rg-stat-label">Femme</span>
        </div>
    </div>
    <div class="rg-stat rg-stat--success">
        <span class="rg-stat-ico"><?= icon('check', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($registreStats['juniors'])) ?></span>
            <span class="rg-stat-label">Enfants</span>
        </div>
    </div>
</div>

<div class="rg-grid-2">
    <section class="rg-card">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('map-pin') ?><span>Par ville</span></h3>
            <span class="rg-badge rg-badge--neutral"><?= (int) $totalVilles ?> villes</span>
        </div>
        <div class="rg-card-body rg-p0">
            <?php if ($registreParVille === []): ?>
                <p class="rg-empty">Aucune ville enregistrée.</p>
            <?php else: ?>
                <div class="rg-table-wrap rg-table-scroll">
                    <table class="rg-table rg-table--compact">
                        <thead>
                            <tr>
                                <th>Ville</th>
                                <th class="rg-num">Homme</th>
                                <th class="rg-num">Femme</th>
                                <th class="rg-num">Enfants</th>
                                <th class="rg-num">Total</th>
                                <th>Capacité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registreParVille as $row): ?>
                                <?php
                                $total    = (int) $row['total'];
                                $capacity = (int) ($row['population'] ?? 0);
                                $rate     = $capacity > 0 ? min(100, (int) round(($total / $capacity) * 100)) : 0;
                                $isFull   = $capacity > 0 && $total >= $capacity;
                                $location = implode(' · ', array_filter([
                                    (string) ($row['arrondissement'] ?? ''),
                                    (string) ($row['commune'] ?? ''),
                                    (string) ($row['departement'] ?? ''),
                                ]));
                                ?>
                                <tr>
                                    <td>
                                        <span class="rg-strong"><?= e($row['nom']) ?></span>
                                        <div class="rg-muted rg-small"><?= e($location) ?></div>
                                    </td>
                                    <td class="rg-num"><?= e(format_number($row['hommes'])) ?></td>
                                    <td class="rg-num"><?= e(format_number($row['femmes'])) ?></td>
                                    <td class="rg-num"><?= e(format_number($row['juniors'])) ?></td>
                                    <td class="rg-num rg-strong"><?= e(format_number($total)) ?></td>
                                    <td class="rg-bar-cell">
                                        <span class="rg-bar-track rg-bar-track--sm">
                                            <span class="rg-bar-fill<?= $isFull ? ' rg-bar-fill--danger' : '' ?>" style="width: <?= $rate ?>%"></span>
                                        </span>
                                        <span class="rg-muted rg-small"><?= e(format_number($total)) ?> / <?= e(format_number($capacity)) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="rg-card">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('map') ?><span>Par arrondissement</span></h3>
            <span class="rg-badge rg-badge--neutral"><?= (int) $totalArrondissements ?> arrondissements</span>
        </div>
        <div class="rg-card-body rg-p0">
            <?php if ($registreParArrondissement === [] && (int) $registreSansArrondissement['total'] === 0): ?>
                <p class="rg-empty">Aucun arrondissement enregistré.</p>
            <?php else: ?>
                <div class="rg-table-wrap rg-table-scroll">
                    <table class="rg-table rg-table--compact">
                        <thead>
                            <tr>
                                <th>Arrondissement</th>
                                <th class="rg-num">Homme</th>
                                <th class="rg-num">Femme</th>
                                <th class="rg-num">Enfants</th>
                                <th class="rg-num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registreParArrondissement as $row): ?>
                                <tr>
                                    <td>
                                        <span class="rg-strong"><?= e($row['nom']) ?></span>
                                        <div class="rg-muted rg-small"><?= e($row['commune'] . ' · ' . $row['departement']) ?></div>
                                    </td>
                                    <td class="rg-num"><?= e(format_number($row['hommes'])) ?></td>
                                    <td class="rg-num"><?= e(format_number($row['femmes'])) ?></td>
                                    <td class="rg-num"><?= e(format_number($row['juniors'])) ?></td>
                                    <td class="rg-num rg-strong"><?= e(format_number($row['total'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ((int) $registreSansArrondissement['total'] > 0): ?>
                                <tr>
                                    <td>
                                        <span class="rg-strong"><?= e($registreSansArrondissement['nom']) ?></span>
                                        <div class="rg-muted rg-small">Villes sans arrondissement</div>
                                    </td>
                                    <td class="rg-num"><?= e(format_number($registreSansArrondissement['hommes'])) ?></td>
                                    <td class="rg-num"><?= e(format_number($registreSansArrondissement['femmes'])) ?></td>
                                    <td class="rg-num"><?= e(format_number($registreSansArrondissement['juniors'])) ?></td>
                                    <td class="rg-num rg-strong"><?= e(format_number($registreSansArrondissement['total'])) ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="rg-card">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('building') ?><span>Par commune</span></h3>
            <span class="rg-badge rg-badge--neutral"><?= (int) $totalCommunes ?> communes</span>
        </div>
        <div class="rg-card-body rg-p0">
            <?php if ($registreParCommune === []): ?>
                <p class="rg-empty">Aucune commune enregistrée.</p>
            <?php else: ?>
                <div class="rg-table-wrap rg-table-scroll">
                    <table class="rg-table rg-table--compact">
                        <thead>
                            <tr>
                                <th>Commune</th>
                                <th class="rg-num">Homme</th>
                                <th class="rg-num">Femme</th>
                                <th class="rg-num">Enfants</th>
                                <th class="rg-num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registreParCommune as $row): ?>
                                <tr>
                                    <td>
                                        <span class="rg-strong"><?= e($row['nom']) ?></span>
                                        <div class="rg-muted rg-small"><?= e($row['departement']) ?></div>
                                    </td>
                                    <td class="rg-num"><?= e(format_number($row['hommes'])) ?></td>
                                    <td class="rg-num"><?= e(format_number($row['femmes'])) ?></td>
                                    <td class="rg-num"><?= e(format_number($row['juniors'])) ?></td>
                                    <td class="rg-num rg-strong"><?= e(format_number($row['total'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="rg-card">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('layers') ?><span>Par département</span></h3>
            <span class="rg-badge rg-badge--neutral"><?= (int) $totalDepartements ?> départements</span>
        </div>
        <div class="rg-card-body rg-p0">
            <?php if ($registreParDepartement === []): ?>
                <p class="rg-empty">Aucun département enregistré.</p>
            <?php else: ?>
                <div class="rg-table-wrap rg-table-scroll">
                    <table class="rg-table rg-table--compact">
                        <thead>
                            <tr>
                                <th>Département</th>
                                <th class="rg-num">Homme</th>
                                <th class="rg-num">Femme</th>
                                <th class="rg-num">Enfants</th>
                                <th class="rg-num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registreParDepartement as $row): ?>
                                <tr>
                                    <td class="rg-strong"><?= e($row['nom']) ?></td>
                                    <td class="rg-num"><?= e(format_number($row['hommes'])) ?></td>
                                    <td class="rg-num"><?= e(format_number($row['femmes'])) ?></td>
                                    <td class="rg-num"><?= e(format_number($row['juniors'])) ?></td>
                                    <td class="rg-num rg-strong"><?= e(format_number($row['total'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

