<?php
/**
 * Renders and clears every pending flash message.
 */
if (!empty($_SESSION['_flash'])) {
    foreach ($_SESSION['_flash'] as $key => $data) {
        $alertClass = match ($data['type'] ?? 'info') {
            'success' => 'success',
            'error'   => 'danger',
            'warning' => 'warning',
            default   => 'info',
        };
        echo '<div class="alert alert-' . e($alertClass) . ' py-2 px-3 small mb-3" role="alert">' . e($data['message']) . '</div>';
        unset($_SESSION['_flash'][$key]);
    }
}
