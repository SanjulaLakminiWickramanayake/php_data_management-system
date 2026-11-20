<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/modules.php';
require_auth();
$key = input_string($_GET, 'module');
$definition = module_definition($key);
$pageTitle = $definition['title'];
$result = module_rows($key, true);
$pageSize = (int) input_string($_GET, 'per_page', '10');
if (!in_array($pageSize, [10, 25, 50, 100], true)) {
    $pageSize = 10;
}
$pageCount = max(1, (int) ceil($result['total'] / $pageSize));
$page = min(max(1, (int) input_string($_GET, 'page', '1')), $pageCount);
$start = $result['total'] === 0 ? 0 : (($page - 1) * $pageSize + 1);
$end = min($page * $pageSize, $result['total']);
$query = $_GET;
unset($query['format']);
unset($query['module']);
$exportQuery = http_build_query($query);
$mailQuery = [];
foreach (['q', 'sort', 'dir'] as $field) {
    if (isset($_GET[$field]) && is_scalar($_GET[$field])) {
        $mailQuery[$field] = (string) $_GET[$field];
    }
}
foreach (array_keys($definition['filters'] ?? []) as $field) {
    if (isset($_GET[$field]) && is_scalar($_GET[$field])) {
        $mailQuery[$field] = (string) $_GET[$field];
    }
}
$attachmentName = $key . '_' . date('Y-m-d') . '.pdf';
$base = 'modules/list.php?module=' . rawurlencode($key);
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow">DATA MANAGEMENT</div><h1><?= e($definition['title']) ?></h1><p><?= e($definition['description']) ?></p></div>
    <a class="btn btn-primary" href="<?= e(url('modules/form.php?module=' . rawurlencode($key))) ?>"><i class="bi bi-plus-lg"></i> Add <?= e($definition['singular']) ?></a>
</div>
<section class="panel">
    <form class="toolbar" method="get" action="<?= e(url('modules/list.php')) ?>">
        <input type="hidden" name="module" value="<?= e($key) ?>">
        <label class="search-field"><i class="bi bi-search"></i><input type="search" name="q" value="<?= e(input_string($_GET, 'q')) ?>" placeholder="Search <?= e(strtolower($definition['title'])) ?>..." aria-label="Search records"></label>
        <?php foreach ($definition['filters'] ?? [] as $field => $filter): ?>
            <?php if ($filter['type'] === 'select'): ?>
                <select class="form-select filter-control" name="<?= e($field) ?>" aria-label="<?= e($filter['label']) ?>">
                    <option value=""><?= e($filter['label']) ?>: all</option>
                    <?php foreach ($filter['options'] as $option): ?>
                        <option value="<?= e($option) ?>"<?= (input_string($_GET, $field) === $option) ? ' selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <label class="date-filter"><span><?= e($filter['label']) ?></span><input type="date" name="<?= e($field) ?>" value="<?= e(input_string($_GET, $field)) ?>"></label>
            <?php endif; ?>
        <?php endforeach; ?>
        <select class="form-select sort-control" name="sort" aria-label="Sort records">
            <?php foreach ($definition['sort'] as $sortKey => $sortSql): $sortName = is_int($sortKey) ? $sortSql : $sortKey; ?>
                <option value="<?= e($sortName) ?>"<?= (input_string($_GET, 'sort') === $sortName) ? ' selected' : '' ?>>Sort: <?= e($definition['columns'][$sortName] ?? ucfirst(str_replace('_', ' ', $sortName))) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select sort-control" name="dir" aria-label="Sort direction">
            <option value="asc"<?= (input_string($_GET, 'dir', 'asc') === 'asc') ? ' selected' : '' ?>>Ascending</option>
            <option value="desc"<?= (input_string($_GET, 'dir') === 'desc') ? ' selected' : '' ?>>Descending</option>
        </select>
        <input type="hidden" name="per_page" value="<?= $pageSize ?>">
        <button class="btn btn-outline-secondary" type="submit">Apply</button>
        <?php if (($_GET['q'] ?? '') !== '' || array_intersect(array_keys($_GET), array_keys($definition['filters'] ?? []))): ?>
            <a class="btn btn-light" href="<?= e(url('modules/list.php?module=' . rawurlencode($key))) ?>">Clear</a>
        <?php endif; ?>
    </form>
    <div class="export-bar">
        <div class="record-summary"><strong><?= number_format($result['total']) ?></strong> records <span>·</span> Showing <?= number_format($start) ?>–<?= number_format($end) ?></div>
        <div class="export-actions">
            <label class="page-size">Rows
                <select class="form-select form-select-sm" data-page-size>
                    <?php foreach ([10, 25, 50, 100] as $size): ?><option value="<?= $size ?>"<?= $pageSize === $size ? ' selected' : '' ?>><?= $size ?></option><?php endforeach; ?>
                </select>
            </label>
            <?php foreach (['pdf' => 'PDF', 'excel' => 'Excel', 'csv' => 'CSV', 'print' => 'Print'] as $format => $label): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('exports/export.php?module=' . rawurlencode($key) . '&format=' . $format . ($exportQuery ? '&' . $exportQuery : ''))) ?>"<?= $format === 'print' ? ' target="_blank" rel="noopener"' : '' ?>><i class="bi <?= $format === 'print' ? 'bi-printer' : 'bi-download' ?>"></i> <?= e($label) ?></a>
            <?php endforeach; ?>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-mail-open onclick="openReportMailDialog(event)"><i class="bi bi-envelope"></i> Send Mail</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table data-table">
            <thead><tr>
                <?php foreach ($definition['columns'] as $column): ?><th><?= e($column) ?></th><?php endforeach; ?>
                <th class="actions-heading">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <?php foreach ($definition['columns'] as $field => $label): ?>
                        <td>
                        <?php if (str_contains($field, 'fee') || $field === 'amount'): ?>
                            <span class="amount"><?= e(number_format((float) ($row[$field] ?? 0), 2)) ?></span>
                        <?php elseif (in_array($field, ['status', 'payment_status'], true)): ?>
                            <span class="status-badge status-<?= e(strtolower((string) ($row[$field] ?? ''))) ?>"><?= e($row[$field] ?? '') ?></span>
                        <?php else: ?>
                            <?= e($row[$field] ?? '—') ?>
                        <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="row-actions">
                        <a class="icon-button" href="<?= e(url('modules/view.php?module=' . rawurlencode($key) . '&id=' . (int) $row[$definition['id']])) ?>" aria-label="View record"><i class="bi bi-eye"></i></a>
                        <a class="icon-button" href="<?= e(url('modules/form.php?module=' . rawurlencode($key) . '&id=' . (int) $row[$definition['id']])) ?>" aria-label="Edit record"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= e(url('modules/delete.php')) ?>" data-confirm="Delete this <?= e(strtolower($definition['singular'])) ?>? This action cannot be undone.">
                            <?= csrf_field() ?><input type="hidden" name="module" value="<?= e($key) ?>"><input type="hidden" name="id" value="<?= (int) $row[$definition['id']] ?>">
                            <button class="icon-button danger" type="submit" aria-label="Delete record"><i class="bi bi-trash3"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?><tr><td class="empty-state" colspan="<?= count($definition['columns']) + 1 ?>"><i class="bi bi-inbox"></i><strong>No records found</strong><span>Try changing your search or filters.</span></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span>Page <?= $page ?> of <?= $pageCount ?></span>
        <nav class="pagination-links" aria-label="Pagination">
            <?php if ($page > 1): $pageQuery = $_GET; $pageQuery['module'] = $key; $pageQuery['page'] = $page - 1; ?>
                <a href="<?= e(url('modules/list.php?' . http_build_query($pageQuery))) ?>" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>
            <?php else: ?><span class="pagination-disabled"><i class="bi bi-chevron-left"></i></span><?php endif; ?>
            <?php for ($number = max(1, $page - 2); $number <= min($pageCount, $page + 2); $number++): $pageQuery = $_GET; $pageQuery['module'] = $key; $pageQuery['page'] = $number; ?>
                <a class="<?= $number === $page ? 'current' : '' ?>" href="<?= e(url('modules/list.php?' . http_build_query($pageQuery))) ?>"><?= $number ?></a>
            <?php endfor; ?>
            <?php if ($page < $pageCount): $pageQuery = $_GET; $pageQuery['module'] = $key; $pageQuery['page'] = $page + 1; ?>
                <a href="<?= e(url('modules/list.php?' . http_build_query($pageQuery))) ?>" aria-label="Next page"><i class="bi bi-chevron-right"></i></a>
            <?php else: ?><span class="pagination-disabled"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
        </nav>
    </div>
</section>
<div class="mail-dialog" data-mail-dialog role="dialog" aria-modal="true" aria-labelledby="mail-dialog-title" hidden onclick="if(event.target === this) closeReportMailDialog(event)">
    <form class="mail-form" method="post" action="<?= e(url('exports/send-mail.php')) ?>" data-mail-form>
        <?= csrf_field() ?>
        <input type="hidden" name="module" value="<?= e($key) ?>">
        <?php foreach ($mailQuery as $field => $value): ?>
            <input type="hidden" name="<?= e($field) ?>" value="<?= e($value) ?>">
        <?php endforeach; ?>
        <header class="mail-dialog-header">
            <div><span class="mail-eyebrow"><i class="bi bi-send"></i> REPORT DELIVERY</span><h2 id="mail-dialog-title">Send Report by E-mail</h2></div>
            <button class="mail-close-icon" type="button" data-mail-close aria-label="Close" onclick="closeReportMailDialog(event)"><i class="bi bi-x-lg"></i></button>
        </header>
        <div class="mail-dialog-body">
            <div class="mail-status" data-mail-status role="status" aria-live="polite" hidden></div>
            <div class="mail-field">
                <label class="form-label" for="mail-to">To <span class="required-mark">*</span></label>
                <input class="form-control" id="mail-to" name="to" type="email" maxlength="254" autocomplete="email" placeholder="recipient@example.com" required>
            </div>
            <div class="mail-field">
                <label class="form-label" for="mail-cc">Cc <span class="mail-optional">(optional)</span></label>
                <input class="form-control" id="mail-cc" name="cc" type="email" maxlength="254" placeholder="cc@example.com">
            </div>
            <div class="mail-field">
                <label class="form-label" for="mail-subject">Subject <span class="required-mark">*</span></label>
                <input class="form-control" id="mail-subject" name="subject" type="text" maxlength="200" value="<?= e($definition['title'] . ' report — ' . date('F j, Y')) ?>" required>
            </div>
            <div class="mail-field">
                <label class="form-label" for="mail-message">Message <span class="required-mark">*</span></label>
                <textarea class="form-control" id="mail-message" name="message" rows="4" maxlength="10000" required>Please find the attached <?= e(strtolower($definition['title'])) ?> report.</textarea>
            </div>
            <div class="mail-attachment">
                <span class="mail-pdf-icon"><i class="bi bi-file-earmark-pdf"></i></span>
                <span><strong><?= e($definition['title']) ?> report</strong><small>The PDF is attached automatically: <?= e($attachmentName) ?></small></span>
                <i class="bi bi-check-circle-fill mail-attachment-check" aria-label="Attached automatically"></i>
            </div>
        </div>
        <footer class="mail-dialog-footer">
            <button class="btn btn-light" type="button" data-mail-close onclick="closeReportMailDialog(event)">Close</button>
            <button class="btn btn-primary" type="submit" data-mail-submit><i class="bi bi-send"></i> Send E-mail</button>
        </footer>
    </form>
</div>
<script>
/* Fallback modal controls: independent of the shared JavaScript bundle. */
window.openReportMailDialog = function (event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    const dialog = document.querySelector('[data-mail-dialog]');
    const form = document.querySelector('[data-mail-form]');
    if (!dialog || !form) {
        console.error('Send Mail dialog markup is missing from this report page.');
        return;
    }
    dialog.hidden = false;
    dialog.style.display = 'grid';
    document.body.classList.add('mail-dialog-open');
    const recipient = form.querySelector('[name="to"]');
    if (recipient) recipient.focus();
};
window.closeReportMailDialog = function (event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    const dialog = document.querySelector('[data-mail-dialog]');
    if (!dialog) return;
    dialog.hidden = true;
    dialog.style.display = 'none';
    document.body.classList.remove('mail-dialog-open');
};
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') window.closeReportMailDialog(event);
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
