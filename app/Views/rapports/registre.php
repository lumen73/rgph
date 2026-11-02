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
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2>Profils du registre</h2>
        <p class="rg-muted">Répartition des profils pour chaque niveau administratif.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('rapports') ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Rapports</span></a>
        <?php if (can('registre.view')): ?>
            <a href="<?= url('registres') ?>" class="rg-btn rg-btn--ghost"><?= icon('users') ?><span>Gérer le registre</span></a>
        <?php endif; ?>
        <?php if (can('export')): ?>
            <a href="<?= url('export/registres') ?>" class="rg-btn rg-btn--primary" download><?= icon('download') ?><span>Export CSV</span></a>
        <?php endif; ?>
    </div>
</div>

<?php include VIEW_PATH . '/partials/registre_tables.php'; ?>
