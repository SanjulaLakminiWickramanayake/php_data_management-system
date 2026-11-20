<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/modules.php';
require_auth();
$key = input_string($_GET, 'module');
$definition = module_definition($key);
$idValue = $_GET['id'] ?? null;
$id = is_scalar($idValue) ? filter_var($idValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
if ($id === false || $id === null) {
    http_response_code(400);
    exit('Invalid record identifier.');
}
$record = $id ? module_record($key, (int) $id) : null;
if (!$record) {
    flash('warning', $definition['singular'] . ' record was not found.');
    redirect($key . '/index.php');
}
$pageTitle = 'View ' . $definition['singular'];
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow"><?= e(strtoupper($definition['title'])) ?></div><h1><?= e($pageTitle) ?></h1><p>Record details and associated information.</p></div>
    <div class="heading-actions"><a class="btn btn-outline-secondary" href="<?= e(url($key . '/index.php')) ?>"><i class="bi bi-arrow-left"></i> Back</a><a class="btn btn-primary" href="<?= e(url('modules/form.php?module=' . rawurlencode($key) . '&id=' . (int) $id)) ?>"><i class="bi bi-pencil"></i> Edit</a></div>
</div>
<section class="panel detail-panel">
    <div class="detail-title"><span class="detail-avatar"><i class="bi <?= e(match ($key) { 'students' => 'bi-person', 'courses' => 'bi-journal-bookmark', 'instructors' => 'bi-person-workspace', 'enrollments' => 'bi-card-checklist', default => 'bi-credit-card' }) ?>"></i></span><div><h2><?= e($record['full_name'] ?? $record['course_name'] ?? $record['student_name'] ?? $definition['singular'] . ' #' . (int) $id) ?></h2><span><?= e($definition['singular']) ?> record · #<?= (int) $id ?></span></div></div>
    <dl class="detail-grid">
        <?php foreach ($definition['fields'] as $name => $field):
            $value = $record[$name] ?? '';
            if ($field['type'] === 'relation' && $value !== '') {
                foreach (module_options($field['source']) as $option) {
                    if ((int) $option['id'] === (int) $value) {
                        $value = $option['label'];
                        break;
                    }
                }
            }
            if ($name === 'course_fee' || $name === 'amount') {
                $value = number_format((float) $value, 2);
            }
        ?>
            <div class="detail-item"><dt><?= e($field['label']) ?></dt><dd><?= ($value === '' || $value === null) ? '—' : nl2br(e($value)) ?></dd></div>
        <?php endforeach; ?>
    </dl>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
