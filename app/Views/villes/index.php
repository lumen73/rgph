<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var \App\Core\Paginator $villes */
/** @var array<int, array<string, mixed>> $departements */
/** @var array<int, array<string, mixed>> $communes */

$sortUrl = static function (string $column) use ($search, $departementId, $communeId, $sort, $direction): string {
    $next = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';

    return url('villes', array_filter([
        'q'           => $search !== '' ? $search : null,
        'departement' => $departementId > 0 ? $departementId : null,
        'commune'     => $communeId > 0 ? $communeId : null,
        'tri'         => $column,
        'ordre'       => $next,
    ], static function ($value) {
        return $value !== null;
    }));
};

$sortIndicator = static function (string $column) use ($sort, $direction): string {
    if ($sort !== $column) {
        return icon('chevron-down', 'ico ico--sm rg-sort-ico');
    }

    return $direction === 'asc'
        ? icon('chevron-up', 'ico ico--sm rg-sort-ico is-active')
        : icon('chevron-down', 'ico ico--sm rg-sort-ico is-active');
};
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Villes</h2>
        <p class="rg-muted"><?php $totalVilles = (int) $villes->total(); ?><?= e(format_number($totalVilles)) ?> <?= $totalVilles > 1 ? 'villes référencées' : 'ville référencée' ?> dans la base.</p>
    </div>
    <div class="rg-actions">
        <?php if (can('export')): ?>
            <a href="<?= url('export/villes') ?>" class="rg-btn rg-btn--ghost"><?= icon('download') ?><span>Export CSV</span></a>
        <?php endif; ?>
        <?php if (can('ville.create')): ?>
            <a href="<?= url('villes/creer-multiple') ?>" class="rg-btn rg-btn--ghost"><?= icon('layers') ?><span>Saisie multiple</span></a>
            <a href="<?= url('villes/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Nouvelle ville</span></a>
        <?php endif; ?>
    </div>
</div>

<div class="rg-card">
    <form method="get" action="<?= url('villes') ?>" class="rg-filters">
        <?= route_field('villes') ?>
        <div class="rg-filter rg-filter--grow">
            <label class="rg-label" for="q">Recherche</label>
            <input type="search" id="q" name="q" value="<?= e($search) ?>" class="rg-input" placeholder="Nom de la ville…">
        </div>
        <div class="rg-filter">
            <label class="rg-label" for="departement">Département</label>
            <select id="departement" name="departement" class="rg-select"
                    data-cascade-url="<?= url('api/communes') ?>" data-cascade-target="#filtre-commune">
                <option value="">Tous</option>
                <?php foreach ($departements as $departement): ?>
                    <option value="<?= (int) $departement['iddepartement'] ?>" <?= $departementId === (int) $departement['iddepartement'] ? 'selected' : '' ?>>
                        <?= e($departement['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rg-filter">
            <label class="rg-label" for="filtre-commune">Commune</label>
            <select id="filtre-commune" name="commune" class="rg-select">
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
            <a href="<?= url('villes') ?>" class="rg-btn rg-btn--ghost">Réinitialiser</a>
        </div>
    </form>
</div>

<div class="rg-card">
    <div class="rg-card-body rg-p0">
        <?php if ($villes->isEmpty()): ?>
            <div class="rg-empty">
                <span class="rg-empty-ico"><?= icon('map-pin', 'ico ico--xl') ?></span>
                <p>Aucune ville ne correspond à votre recherche.</p>
                <?php if (can('ville.create')): ?>
                    <a href="<?= url('villes/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Ajouter une ville</span></a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="rg-table-wrap">
                <table class="rg-table">
                    <thead>
                        <tr>
                            <th><a href="<?= e($sortUrl('nom')) ?>" class="rg-th-sort">Ville <?= $sortIndicator('nom') ?></a></th>
                            <th>Arrondissement</th>
                            <th><a href="<?= e($sortUrl('commune')) ?>" class="rg-th-sort">Commune <?= $sortIndicator('commune') ?></a></th>
                            <th>Département</th>
                            <th class="rg-num"><a href="<?= e($sortUrl('population')) ?>" class="rg-th-sort">Population <?= $sortIndicator('population') ?></a></th>
                            <th class="rg-num">Profils</th>
                            <th class="rg-num">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($villes->items() as $ville): ?>
                            <tr>
                                <td class="rg-strong">
                                    <a href="<?= url('villes/' . (int) $ville['idville']) ?>" class="rg-link"><?= e($ville['nom']) ?></a>
                                </td>
                                <td>
                                    <?php if (!empty($ville['arrondissement'])): ?>
                                        <?= e($ville['arrondissement']) ?>
                                    <?php else: ?>
                                        <span class="rg-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($ville['commune']) ?></td>
                                <td><?= e($ville['departement']) ?></td>
                                <td class="rg-num rg-strong"><?= e(format_number($ville['population'])) ?></td>
                                <td class="rg-num">
                                    <?php
                                    $registered = (int) ($ville['nb_registres'] ?? 0);
                                    $capacity   = (int) ($ville['population'] ?? 0);
                                    ?>
                                    <span class="rg-nowrap"><?= e(format_number($registered)) ?> / <?= e(format_number($capacity)) ?></span>
                                    <?php if ($capacity === 0): ?>
                                        <span class="rg-badge rg-badge--warn">Capacité 0</span>
                                    <?php elseif ($registered >= $capacity): ?>
                                        <span class="rg-badge rg-badge--danger">Complet</span>
                                    <?php endif; ?>
                                </td>
                                <td class="rg-num">
                                    <div class="rg-row-actions">
                                        <a href="<?= url('villes/' . (int) $ville['idville']) ?>" class="rg-icon-btn rg-icon-btn--sm" title="Consulter"><?= icon('search', 'ico ico--sm') ?></a>
                                        <?php if (can('ville.edit')): ?>
                                            <a href="<?= url('villes/' . (int) $ville['idville'] . '/editer') ?>" class="rg-icon-btn rg-icon-btn--sm" title="Modifier"><?= icon('edit', 'ico ico--sm') ?></a>
                                        <?php endif; ?>
                                        <?php if (can('ville.delete') && $registered === 0): ?>
                                            <form method="post" action="<?= url('villes/' . (int) $ville['idville'] . '/supprimer') ?>" data-confirm="Supprimer la ville « <?= e($ville['nom']) ?> » ?" class="rg-inline-form">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="rg-icon-btn rg-icon-btn--sm rg-icon-btn--danger" title="Supprimer"><?= icon('trash', 'ico ico--sm') ?></button>
                                            </form>
                                        <?php elseif (can('ville.delete')): ?>
                                            <span class="rg-icon-btn rg-icon-btn--sm is-disabled" title="Suppression impossible : des profils sont rattachés."><?= icon('trash', 'ico ico--sm') ?></span>
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

    <?php if ($villes->hasPages()): ?>
        <div class="rg-card-foot">
            <span class="rg-muted"><?= e($villes->from()) ?>–<?= e($villes->to()) ?> sur <?= e(format_number($villes->total())) ?></span>
            <?= paginate_links($villes, 'villes', array_filter([
                'q'           => $search !== '' ? $search : null,
                'departement' => $departementId > 0 ? $departementId : null,
                'commune'     => $communeId > 0 ? $communeId : null,
                'tri'         => $sort,
                'ordre'       => $direction,
            ], static function ($value) {
                return $value !== null;
            })) ?>
        </div>
    <?php endif; ?>
</div>