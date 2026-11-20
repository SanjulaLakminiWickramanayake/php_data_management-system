<?php
declare(strict_types=1);

foreach (take_flashes() as $notice) {
    $type = in_array($notice['type'], ['success', 'danger', 'warning', 'info'], true) ? $notice['type'] : 'info';
    echo '<div class="alert alert-' . e($type) . '" role="alert">' . e($notice['message']) . '</div>';
}
