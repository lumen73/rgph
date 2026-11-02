<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var \App\Core\Paginator $arrondissements */
/** @var array<int, array<string, mixed>> $departements */
/** @var array<int, array<string, mixed>> $communes */
/** @var string $search */
/** @var int $departementId */
/** @var int $communeId */
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Arrondissements</h2>
        <p class="rg-muted"><?php $totalArr = (int) $arrondissements->total(); ?><?= e(format_number($totalArr)) ?> <?= $totalArr > 1 ? 'arrondissements' : 'arrondissement' ?> au total.</p>
    </div>
    <div class="rg-actions">
        <?php if (can('export')): ?>
            <a href="<?= url('export/arrondissements') ?>" class="rg-btn rg-btn--ghost" download><?= icon('download') ?><span>Export CSV</span></a>
        <?php endif; ?>
        <?php if (can('arrondissement.create')): ?>
            <a href="<?= url('arrondissements/creer-multiple') ?>" class="rg-btn rg-btn--ghost"><?= icon('layers') ?><span>Saisie multiple</span></a>
            <a href="<?= url('arrondissements/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Nouvel arrondissement</span></a>
        <?php endif; ?>
    </div>
</div>

<div class="rg-card">
    <form method="get" action="<?= url('arrondissements') ?>" class="rg-filters">
        <?= route_field('arrondissements') ?>
        <div class="rg-filter rg-filter--grow">
            <label class="rg-label" for="q">Recherche</label>
            <input type="search" id="q" name="q" value="<?= e($search) ?>" class="rg-input" placeholder="Nom de l'arrondissement…">
        </div>
        <div class="rg-filter">
            <label class="rg-label" for="departement">Département</label>
            <select id="departement" name="departement" class="rg-select"
                    data-cascade-url="<?= url('api/communes') ?>"
                    data-cascade-target="#commune">
                <option value="">Tous</option>
                <?php foreach ($departements as $departement): ?>
                    <option value="<?= (int) $departement['iddepartement'] ?>" <?= $departementId === (int) $departement['iddepartement'] ? 'selected' : '' ?>>
                        <?= e($departement['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rg-filter">
            <label class="rg-label" for="commune">Commune</label>
            <select id="commune" name="commune" class="rg-select">
                <option value="">Toutes</option>
                <?php foreach ($communes as $commune): ?>
                    <option value="<?= (int) $commune['idcommune'] ?>" <?= $communeId === (int) $commune['idcommune'] ? 'selected' : '' ?>>
                        <?= e($commune['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rg-filter rg-filter--actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('filter') ?><span>Filtrer</span></button>
            <a href="<?= url('arrondissements') ?>" class="rg-btn rg-btn--ghost">Réinitialiser</a>
        </div>
    </form>
</div>

<div class="rg-card">
    <div class="rg-card-body rg-p0">
        <?php if ($arrondissements->isEmpty()): ?>
            <div class="rg-empty">
                <span class="rg-empty-ico"><?= icon('map', 'ico ico--xl') ?></span>
                <p>Aucun arrondissement ne correspond à votre recherche.</p>
            </div>
        <?php else: ?>
            <div class="rg-table-wrap">
                <table class="rg-table">
                    <thead>
                        <tr>
                            <th>Arrondissement</th>
                            <th>Commune</th>
                            <th>Département</th>
                            <th class="rg-num">Villes</th>
                            <th class="rg-num">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($arrondissements->items() as $arrondissement): ?>
                            <tr>
                                <td class="rg-strong"><?= e($arrondissement['nom']) ?></td>
                                <td><?= e($arrondissement['commune']) ?></td>
                                <td><?= e($arrondissement['departement']) ?></td>
                                <td class="rg-num"><?= e(format_number($arrondissement['nb_villes'] ?? 0)) ?></td>
                                <td class="rg-num">
                                    <div class="rg-row-actions">
                                        <?php if (can('arrondissement.edit')): ?>
                                            <a href="<?= url('arrondissements/' . (int) $arrondissement['idarrondissement'] . '/editer') ?>" class="rg-icon-btn rg-icon-btn--sm" title="Modifier"><?= icon('edit', 'ico ico--sm') ?></a>
                                        <?php endif; ?>
                                        <?php if (can('arrondissement.delete') && (int) ($arrondissement['nb_villes'] ?? 0) === 0): ?>
                                            <form method="post" action="<?= url('arrondissements/' . (int) $arrondissement['idarrondissement'] . '/supprimer') ?>" data-confirm="Supprimer l'arrondissement « <?= e($arrondissement['nom']) ?> » ?" class="rg-inline-form">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="rg-icon-btn rg-icon-btn--sm rg-icon-btn--danger" title="Supprimer"><?= icon('trash', 'ico ico--sm') ?></button>
                                            </form>
                                        <?php elseif (can('arrondissement.delete')): ?>
                                            <span class="rg-icon-btn rg-icon-btn--sm is-disabled" title="Suppression impossible : des villes sont rattachées."><?= icon('trash', 'ico ico--sm') ?></span>
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

    <?php if ($arrondissements->hasPages()): ?>
        <div class="rg-card-foot">
            <span class="rg-muted"><?= e($arrondissements->from()) ?>–<?= e($arrondissements->to()) ?> sur <?= e(format_number($arrondissements->total())) ?></span>
            <?= paginate_links($arrondissements, 'arrondissements', array_filter([
                'q'           => $search !== '' ? $search : null,
                'departement' => $departementId > 0 ? $departementId : null,
                'commune'     => $communeId > 0 ? $communeId : null,
            ], static function ($value) {
                return $value !== null;
            })) ?>
        </div>
    <?php endif; ?>
</div>
