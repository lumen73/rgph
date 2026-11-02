<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var \App\Core\Paginator $communes */
/** @var array<int, array<string, mixed>> $departements */
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Communes</h2>
        <p class="rg-muted"><?php $totalCom = (int) $communes->total(); ?><?= e(format_number($totalCom)) ?> <?= $totalCom > 1 ? 'communes' : 'commune' ?> au total.</p>
    </div>
    <div class="rg-actions">
        <?php if (can('export')): ?>
            <a href="<?= url('export/communes') ?>" class="rg-btn rg-btn--ghost" download><?= icon('download') ?><span>Export CSV</span></a>
        <?php endif; ?>
        <?php if (can('commune.create')): ?>
            <a href="<?= url('communes/creer-multiple') ?>" class="rg-btn rg-btn--ghost"><?= icon('layers') ?><span>Saisie multiple</span></a>
            <a href="<?= url('communes/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Nouvelle commune</span></a>
        <?php endif; ?>
    </div>
</div>

<div class="rg-card">
    <form method="get" action="<?= url('communes') ?>" class="rg-filters">
        <?= route_field('communes') ?>
        <div class="rg-filter rg-filter--grow">
            <label class="rg-label" for="q">Recherche</label>
            <input type="search" id="q" name="q" value="<?= e($search) ?>" class="rg-input" placeholder="Nom de la commune…">
        </div>
        <div class="rg-filter">
            <label class="rg-label" for="departement">Département</label>
            <select id="departement" name="departement" class="rg-select">
                <option value="">Tous</option>
                <?php foreach ($departements as $departement): ?>
                    <option value="<?= (int) $departement['iddepartement'] ?>" <?= $departementId === (int) $departement['iddepartement'] ? 'selected' : '' ?>>
                        <?= e($departement['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rg-filter rg-filter--actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('filter') ?><span>Filtrer</span></button>
            <a href="<?= url('communes') ?>" class="rg-btn rg-btn--ghost">Réinitialiser</a>
        </div>
    </form>
</div>

<div class="rg-card">
    <div class="rg-card-body rg-p0">
        <?php if ($communes->isEmpty()): ?>
            <div class="rg-empty">
                <span class="rg-empty-ico"><?= icon('building', 'ico ico--xl') ?></span>
                <p>Aucune commune ne correspond à votre recherche.</p>
            </div>
        <?php else: ?>
            <div class="rg-table-wrap">
                <table class="rg-table">
                    <thead>
                        <tr>
                            <th>Commune</th>
                            <th>Département</th>
                            <th class="rg-num">Arrondissements</th>
                            <th class="rg-num">Villes</th>
                            <th class="rg-num">Population</th>
                            <th class="rg-num">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($communes->items() as $commune): ?>
                            <tr>
                                <td class="rg-strong"><?= e($commune['nom']) ?></td>
                                <td><?= e($commune['departement']) ?></td>
                                <td class="rg-num"><?= e(format_number($commune['nb_arrondissements'])) ?></td>
                                <td class="rg-num"><?= e(format_number($commune['nb_villes'])) ?></td>
                                <td class="rg-num rg-strong"><?= e(format_number($commune['population'])) ?></td>
                                <td class="rg-num">
                                    <div class="rg-row-actions">
                                        <?php if (can('commune.edit')): ?>
                                            <a href="<?= url('communes/' . (int) $commune['idcommune'] . '/editer') ?>" class="rg-icon-btn rg-icon-btn--sm" title="Modifier"><?= icon('edit', 'ico ico--sm') ?></a>
                                        <?php endif; ?>
                                        <?php if (can('commune.delete')): ?>
                                            <form method="post" action="<?= url('communes/' . (int) $commune['idcommune'] . '/supprimer') ?>" data-confirm="Supprimer la commune « <?= e($commune['nom']) ?> » ?" class="rg-inline-form">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="rg-icon-btn rg-icon-btn--sm rg-icon-btn--danger" title="Supprimer"><?= icon('trash', 'ico ico--sm') ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($communes->hasPages()): ?>
        <div class="rg-card-foot">
            <span class="rg-muted"><?= e($communes->from()) ?>–<?= e($communes->to()) ?> sur <?= e(format_number($communes->total())) ?></span>
            <?= paginate_links($communes, 'communes', array_filter([
                'q'           => $search !== '' ? $search : null,
                'departement' => $departementId > 0 ? $departementId : null,
            ], static function ($value) {
                return $value !== null;
            })) ?>
        </div>
    <?php endif; ?>
</div>