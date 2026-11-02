<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var \App\Core\Paginator $departements */
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Départements</h2>
        <p class="rg-muted"><?php $totalDep = (int) $departements->total(); ?><?= e(format_number($totalDep)) ?> <?= $totalDep > 1 ? 'départements' : 'département' ?> — découpage administratif de premier niveau.</p>
    </div>
    <div class="rg-actions">
        <?php if (can('departement.manage')): ?>
            <a href="<?= url('departements/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Nouveau département</span></a>
        <?php endif; ?>
    </div>
</div>

<div class="rg-card">
    <form method="get" action="<?= url('departements') ?>" class="rg-filters">
        <?= route_field('departements') ?>
        <div class="rg-filter rg-filter--grow">
            <label class="rg-label" for="q">Recherche</label>
            <input type="search" id="q" name="q" value="<?= e($search) ?>" class="rg-input" placeholder="Nom du département…">
        </div>
        <div class="rg-filter rg-filter--actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('filter') ?><span>Filtrer</span></button>
            <a href="<?= url('departements') ?>" class="rg-btn rg-btn--ghost">Réinitialiser</a>
        </div>
    </form>
</div>

<div class="rg-card">
    <div class="rg-card-body rg-p0">
        <?php if ($departements->isEmpty()): ?>
            <div class="rg-empty">
                <span class="rg-empty-ico"><?= icon('layers', 'ico ico--xl') ?></span>
                <p>Aucun département ne correspond à votre recherche.</p>
            </div>
        <?php else: ?>
            <div class="rg-table-wrap">
                <table class="rg-table">
                    <thead>
                        <tr>
                            <th>Département</th>
                            <th class="rg-num">Communes</th>
                            <th class="rg-num">Villes</th>
                            <th class="rg-num">Population</th>
                            <th class="rg-num">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departements->items() as $departement): ?>
                            <tr>
                                <td class="rg-strong"><?= e($departement['nom']) ?></td>
                                <td class="rg-num"><?= e(format_number($departement['nb_communes'])) ?></td>
                                <td class="rg-num"><?= e(format_number($departement['nb_villes'])) ?></td>
                                <td class="rg-num rg-strong"><?= e(format_number($departement['population'])) ?></td>
                                <td class="rg-num">
                                    <div class="rg-row-actions">
                                        <?php if (can('departement.manage')): ?>
                                            <a href="<?= url('departements/' . (int) $departement['iddepartement'] . '/editer') ?>" class="rg-icon-btn rg-icon-btn--sm" title="Modifier"><?= icon('edit', 'ico ico--sm') ?></a>
                                            <form method="post" action="<?= url('departements/' . (int) $departement['iddepartement'] . '/supprimer') ?>" data-confirm="Supprimer le département « <?= e($departement['nom']) ?> » ?" class="rg-inline-form">
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

    <?php if ($departements->hasPages()): ?>
        <div class="rg-card-foot">
            <span class="rg-muted"><?= e($departements->from()) ?>–<?= e($departements->to()) ?> sur <?= e(format_number($departements->total())) ?></span>
            <?= paginate_links($departements, 'departements', array_filter([
                'q' => $search !== '' ? $search : null,
            ], static function ($value) {
                return $value !== null;
            })) ?>
        </div>
    <?php endif; ?>
</div>