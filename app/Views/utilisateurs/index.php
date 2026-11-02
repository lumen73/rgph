<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var \App\Core\Paginator $utilisateurs */
/** @var array<int, string> $roles */
/** @var string $role */
/** @var array<string, mixed>|null $currentUser */

$roleLabels = ['admin' => 'Administrateur', 'agent' => 'Agent', 'lecteur' => 'Lecteur'];
$roleBadges = ['admin' => 'primary', 'agent' => 'info', 'lecteur' => 'neutral'];
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Utilisateurs</h2>
        <p class="rg-muted"><?php $totalUsers = (int) $utilisateurs->total(); ?><?= e(format_number($totalUsers)) ?> <?= $totalUsers > 1 ? 'comptes' : 'compte' ?> — gestion des accès et des rôles.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('utilisateurs/creer') ?>" class="rg-btn rg-btn--primary"><?= icon('plus') ?><span>Nouvel utilisateur</span></a>
    </div>
</div>

<div class="rg-card">
    <form method="get" action="<?= url('utilisateurs') ?>" class="rg-filters">
        <?= route_field('utilisateurs') ?>
        <div class="rg-filter rg-filter--grow">
            <label class="rg-label" for="q">Recherche</label>
            <input type="search" id="q" name="q" value="<?= e($search) ?>" class="rg-input" placeholder="Nom ou e-mail…">
        </div>
        <div class="rg-filter">
            <label class="rg-label" for="role">Rôle</label>
            <select id="role" name="role" class="rg-select">
                <option value="">Tous</option>
                <?php foreach ($roles as $roleItem): ?>
                    <option value="<?= e($roleItem) ?>" <?= $role === $roleItem ? 'selected' : '' ?>><?= e($roleLabels[$roleItem] ?? $roleItem) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rg-filter rg-filter--actions">
            <button type="submit" class="rg-btn rg-btn--primary"><?= icon('filter') ?><span>Filtrer</span></button>
            <a href="<?= url('utilisateurs') ?>" class="rg-btn rg-btn--ghost">Réinitialiser</a>
        </div>
    </form>
</div>

<div class="rg-card">
    <div class="rg-card-body rg-p0">
        <?php if ($utilisateurs->isEmpty()): ?>
            <div class="rg-empty">
                <span class="rg-empty-ico"><?= icon('users', 'ico ico--xl') ?></span>
                <p>Aucun utilisateur ne correspond à votre recherche.</p>
            </div>
        <?php else: ?>
            <div class="rg-table-wrap">
                <table class="rg-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>E-mail</th>
                            <th>Rôle</th>
                            <th class="rg-num">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs->items() as $utilisateur): ?>
                            <?php $isSelf = (int) $utilisateur['idutilisateur'] === (int) ($currentUser['idutilisateur'] ?? 0); ?>
                            <tr>
                                <td>
                                    <div class="rg-user-cell">
                                        <span class="rg-avatar rg-avatar--sm"><?= e(mb_strtoupper(mb_substr((string) $utilisateur['nom'], 0, 1))) ?></span>
                                        <span class="rg-strong"><?= e($utilisateur['nom']) ?><?= $isSelf ? ' <span class="rg-muted">(vous)</span>' : '' ?></span>
                                    </div>
                                </td>
                                <td class="rg-muted"><?= e($utilisateur['email']) ?></td>
                                <td>
                                    <span class="rg-badge rg-badge--<?= e($roleBadges[$utilisateur['role']] ?? 'neutral') ?>">
                                        <?= e($roleLabels[$utilisateur['role']] ?? $utilisateur['role']) ?>
                                    </span>
                                </td>
                                <td class="rg-num">
                                    <div class="rg-row-actions">
                                        <a href="<?= url('utilisateurs/' . (int) $utilisateur['idutilisateur'] . '/editer') ?>" class="rg-icon-btn rg-icon-btn--sm" title="Modifier"><?= icon('edit', 'ico ico--sm') ?></a>
                                        <?php if (!$isSelf): ?>
                                            <form method="post" action="<?= url('utilisateurs/' . (int) $utilisateur['idutilisateur'] . '/supprimer') ?>" data-confirm="Supprimer le compte « <?= e($utilisateur['nom']) ?> » ?" class="rg-inline-form">
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

    <?php if ($utilisateurs->hasPages()): ?>
        <div class="rg-card-foot">
            <span class="rg-muted"><?= e($utilisateurs->from()) ?>–<?= e($utilisateurs->to()) ?> sur <?= e(format_number($utilisateurs->total())) ?></span>
            <?= paginate_links($utilisateurs, 'utilisateurs', array_filter([
                'q'    => $search !== '' ? $search : null,
                'role' => $role !== '' ? $role : null,
            ], static function ($value) {
                return $value !== null;
            })) ?>
        </div>
    <?php endif; ?>
</div>