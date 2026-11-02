<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var \App\Core\Paginator $registres */
/** @var array<string, int> $stats */
/** @var array<int, array<string, mixed>> $villes */
/** @var string $search */
/** @var int $villeId */
/** @var string $profil */
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Registre</h2>
        <p class="rg-muted">Données individuelles de chaque citoyen.</p>
    </div>
    <div class="rg-actions">
        <?php if (can('export')): ?>
            <a href="<?= url('export/registres') ?>" class="rg-btn rg-btn--ghost" download><?= icon('download') ?><span>Export CSV</span></a>
        <?php endif; ?>
        <?php if (can('registre.create')): ?>
            <a href="<?= url('registres/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Nouveau profil</span></a>
        <?php endif; ?>
    </div>
</div>

<div class="rg-stats">
    <div class="rg-stat rg-stat--primary">
        <span class="rg-stat-ico"><?= icon('users', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($stats['total'])) ?></span>
            <span class="rg-stat-label">Profils enregistrés</span>
        </div>
    </div>
    <div class="rg-stat rg-stat--info">
        <span class="rg-stat-ico"><?= icon('user', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($stats['hommes'])) ?></span>
            <span class="rg-stat-label">Homme</span>
        </div>
    </div>
    <div class="rg-stat rg-stat--warn">
        <span class="rg-stat-ico"><?= icon('user', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($stats['femmes'])) ?></span>
            <span class="rg-stat-label">Femme</span>
        </div>
    </div>
    <div class="rg-stat rg-stat--success">
        <span class="rg-stat-ico"><?= icon('check', 'ico ico--lg') ?></span>
        <div class="rg-stat-body">
            <span class="rg-stat-value"><?= e(format_number($stats['juniors'])) ?></span>
            <span class="rg-stat-label">Enfants</span>
        </div>
    </div>
</div>

<div class="rg-card">
    <form method="get" action="<?= url('registres') ?>" class="rg-filters">
        <?= route_field('registres') ?>
        <div class="rg-filter rg-filter--grow">
            <label class="rg-label" for="q">Recherche</label>
            <input type="search" id="q" name="q" value="<?= e($search) ?>" class="rg-input" placeholder="Nom ou prénom…">
        </div>
        <div class="rg-filter">
            <label class="rg-label" for="ville">Ville</label>
            <select id="ville" name="ville" class="rg-select">
                <option value="">Toutes</option>
                <?php foreach ($villes as $ville): ?>
                    <option value="<?= (int) $ville['idville'] ?>" <?= $villeId === (int) $ville['idville'] ? 'selected' : '' ?>>
                        <?= e($ville['nom'] . ' — ' . $ville['commune']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rg-filter">
            <label class="rg-label" for="profil">Profil</label>
            <select id="profil" name="profil" class="rg-select">
                <option value="">Tous</option>
                <option value="M" <?= $profil === 'M' ? 'selected' : '' ?>>Homme</option>
                <option value="F" <?= $profil === 'F' ? 'selected' : '' ?>>Femme</option>
                <option value="junior" <?= $profil === 'junior' ? 'selected' : '' ?>>Enfants</option>
            </select>
        </div>
        <div class="rg-filter rg-filter--actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('filter') ?><span>Filtrer</span></button>
            <a href="<?= url('registres') ?>" class="rg-btn rg-btn--ghost">Réinitialiser</a>
        </div>
    </form>
</div>

<div class="rg-card">
    <div class="rg-card-body rg-p0">
        <?php if ($registres->isEmpty()): ?>
            <div class="rg-empty">
                <span class="rg-empty-ico"><?= icon('users', 'ico ico--xl') ?></span>
                <p>Aucun profil ne correspond à votre recherche.</p>
                <?php if (can('registre.create')): ?>
                    <a href="<?= url('registres/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Ajouter un profil</span></a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="rg-table-wrap">
                <table class="rg-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Date de naissance</th>
                            <th class="rg-num">Âge</th>
                            <th>Genre</th>
                            <th>Localisation</th>
                            <th class="rg-num">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registres->items() as $registre): ?>
                            <?php
                            $age      = (int) ($registre['age'] ?? 0);
                            $isJunior = $age < 18;
                            ?>
                            <tr>
                                <td class="rg-strong"><?= e($registre['nom']) ?></td>
                                <td><?= e($registre['prenom']) ?></td>
                                <td class="rg-nowrap"><?= e($registre['date_ref']) ?></td>
                                <td class="rg-num"><?= e($age) ?> ans</td>
                                <td>
                                    <?php if ($registre['genre'] === 'M'): ?>
                                        <span class="rg-badge rg-badge--primary">M</span>
                                    <?php else: ?>
                                        <span class="rg-badge rg-badge--warn">F</span>
                                    <?php endif; ?>
                                    <?php if ($isJunior): ?>
                                        <span class="rg-badge rg-badge--success">Enfants</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="rg-strong"><?= e($registre['ville']) ?></span>
                                    <div class="rg-muted rg-small">
                                        <?= e($registre['commune'] . ' · ' . $registre['departement']) ?>
                                        <?php if (!empty($registre['arrondissement'])): ?>
                                            · <?= e($registre['arrondissement']) ?>
                                        <?php else: ?>
                                            · Non rattaché
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="rg-num">
                                    <div class="rg-row-actions">
                                        <?php if (can('registre.edit')): ?>
                                            <a href="<?= url('registres/' . (int) $registre['idregistre'] . '/editer') ?>" class="rg-icon-btn rg-icon-btn--sm" title="Modifier"><?= icon('edit', 'ico ico--sm') ?></a>
                                        <?php endif; ?>
                                        <?php if (can('registre.delete')): ?>
                                            <form method="post" action="<?= url('registres/' . (int) $registre['idregistre'] . '/supprimer') ?>" data-confirm="Supprimer le profil « <?= e($registre['prenom'] . ' ' . $registre['nom']) ?> » ?" class="rg-inline-form">
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

    <?php if ($registres->hasPages()): ?>
        <div class="rg-card-foot">
            <span class="rg-muted"><?= e($registres->from()) ?>–<?= e($registres->to()) ?> sur <?= e(format_number($registres->total())) ?></span>
            <?= paginate_links($registres, 'registres', array_filter([
                'q'      => $search !== '' ? $search : null,
                'ville'  => $villeId > 0 ? $villeId : null,
                'profil' => $profil !== '' ? $profil : null,
            ], static function ($value) {
                return $value !== null;
            })) ?>
        </div>
    <?php endif; ?>
</div>
