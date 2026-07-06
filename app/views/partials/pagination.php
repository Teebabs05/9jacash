<?php
/** @var int $page @var int $total @var int $perPage @var string $baseUrl */
$totalPages = max(1, (int) ceil($total / max(1, $perPage)));
if ($totalPages <= 1) return;
$sep = str_contains($baseUrl, '?') ? '&' : '?';
?>
<nav class="d-flex justify-content-center mt-4">
    <ul class="pagination">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e($baseUrl . $sep . 'page=' . max(1, $page - 1)) ?>">Prev</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= e($baseUrl . $sep . 'page=' . $i) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e($baseUrl . $sep . 'page=' . min($totalPages, $page + 1)) ?>">Next</a>
        </li>
    </ul>
</nav>
