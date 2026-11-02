<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Core\Session;

$flash = Session::pullFlash();
$icons = [
    'success' => 'check',
    'danger'  => 'alert',
    'warning' => 'alert',
    'info'    => 'info',
];

if ($flash === []) {
    return;
}
?>
<div class="rg-flash">
    <?php foreach ($flash as $message): ?>
        <?php
        $type = in_array($message['type'], ['success', 'danger', 'warning', 'info'], true) ? $message['type'] : 'info';
        // Seuls les messages de réussite/information disparaissent automatiquement.
        $autodismiss = in_array($type, ['success', 'info'], true) ? ' data-autodismiss' : '';
        ?>
        <div class="rg-alert rg-alert--<?= e($type) ?>" role="<?= in_array($type, ['danger', 'warning'], true) ? 'alert' : 'status' ?>"<?= $autodismiss ?>>
            <span class="rg-alert-ico"><?= icon($icons[$type] ?? 'info') ?></span>
            <span class="rg-alert-text"><?= e($message['message']) ?></span>
            <button type="button" class="rg-alert-close" data-dismiss aria-label="Fermer"><?= icon('close', 'ico ico--sm') ?></button>
        </div>
    <?php endforeach; ?>
</div>