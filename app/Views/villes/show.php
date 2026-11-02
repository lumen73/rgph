<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}
/** @var array<string, mixed> $ville */

$registered = (int) ($ville['nb_registres'] ?? 0);
$capacity   = (int) ($ville['population'] ?? 0);
$remaining  = max(0, $capacity - $registered);
$rate       = $capacity > 0 ? min(100, (int) round(($registered / $capacity) * 100)) : 0;
$isFull     = $capacity > 0 && $registered >= $capacity;
?>
<div class="rg-page-head">
    <div class="rg-page-head-titles">
        <h2><?= e($ville['nom']) ?></h2>
        <p class="rg-muted">Fiche détaillée de la ville et de son rattachement administratif.</p>
    </div>
    <div class="rg-actions">
        <a href="<?= url('villes') ?>" class="rg-btn rg-btn--ghost"><?= icon('chevron-left') ?><span>Retour</span></a>
        <?php if (can('registre.view')): ?>
            <a href="<?= url('registres', ['ville' => (int) $ville['idville']]) ?>" class="rg-btn rg-btn--ghost"><?= icon('users') ?><span>Registre de la ville</span></a>
        <?php endif; ?>
        <?php if (can('ville.edit')): ?>
            <a href="<?= url('villes/' . (int) $ville['idville'] . '/editer') ?>" class="rg-btn rg-btn--primary"><?= icon('edit') ?><span>Modifier</span></a>
        <?php endif; ?>
    </div>
</div>

<div class="rg-detail-grid">
    <div class="rg-card rg-detail-main">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('map-pin') ?><span>Informations</span></h3>
           <!-- <span class="rg-badge rg-badge--neutral">ID #  --> <!--<?= e($ville['idville']) ?></span> -->
        </div>
        <div class="rg-card-body">
            <dl class="rg-detail-list">
                <div class="rg-detail-item">
                    <dt>Département</dt>
                    <dd><a href="<?= url('departements') ?>" class="rg-link"><?= e($ville['departement']) ?></a></dd>
                </div>
                <div class="rg-detail-item">
                    <dt>Commune</dt>
                    <dd><a href="<?= url('communes') ?>" class="rg-link"><?= e($ville['commune']) ?></a></dd>
                </div>
                <div class="rg-detail-item">
                    <dt>Arrondissement</dt>
                    <dd>
                        <?php if (!empty($ville['arrondissement'])): ?>
                            <?= e($ville['arrondissement']) ?>
                        <?php else: ?>
                            <span class="rg-muted">Non rattaché</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="rg-detail-item">
                    <dt>Ville</dt>
                    <dd class="rg-strong"><?= e($ville['nom']) ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="rg-card rg-detail-side">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('trending') ?><span>Population Enregistrée</span></h3>
        </div>
        <div class="rg-card-body">
            <span class="rg-big-number"><?= e(format_number($registered)) ?></span>
            <span class="rg-muted">profil(s) enregistré(s) sur <?= e(format_number($capacity)) ?></span>

            <div class="rg-capacity">
                <div class="rg-capacity-head">
                    <span class="rg-muted rg-small">Remplissage</span>
                    <span class="rg-strong rg-small"><?= $rate ?> %</span>
                </div>
                <span class="rg-bar-track">
                    <span class="rg-bar-fill<?= $isFull ? ' rg-bar-fill--danger' : '' ?>" style="width: <?= $rate ?>%"></span>
                </span>
                <p class="rg-hint">
                    <?php if ($capacity === 0): ?>
                        Capacité non définie : aucun profil ne peut être ajouté tant que la population reste à 0.
                    <?php elseif ($isFull): ?>
                        Capacité atteinte : aucun profil supplémentaire ne peut être ajouté.
                    <?php else: ?>
                        Reste <?= e(format_number($remaining)) ?> place(s) disponible(s).
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php if (can('ville.delete')): ?>
    <div class="rg-card rg-card--danger">
        <div class="rg-card-head">
            <h3 class="rg-card-title"><?= icon('alert') ?><span>Zone sensible</span></h3>
        </div>
        <div class="rg-card-body rg-between">
            <?php if ($registered > 0): ?>
                <p class="rg-muted">
                    Suppression impossible : <?= e(format_number($registered)) ?> profil(s) du registre sont
                    rattachés à cette ville. Supprimez d'abord ces profils.
                </p>
                <span class="rg-badge rg-badge--warn"><?= icon('shield', 'ico ico--sm') ?>Protégée</span>
            <?php else: ?>
                <p class="rg-muted">La suppression est définitive et ne peut pas être annulée.</p>
                <form method="post" action="<?= url('villes/' . (int) $ville['idville'] . '/supprimer') ?>" data-confirm="Supprimer définitivement la ville « <?= e($ville['nom']) ?> » ?">
                    <?= csrf_field() ?>
                    <button type="submit" class="rg-btn rg-btn--danger"><?= icon('trash') ?><span>Supprimer la ville</span></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
