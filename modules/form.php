<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/modules.php';
require_auth();
$key = input_string($_GET, 'module');
$definition = module_definition($key);
$idInput = $_GET['id'] ?? null;
$id = $idInput === null ? null : (is_scalar($idInput) ? filter_var($idInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false);
if ($idInput !== null && $id === false) {
    http_response_code(400);
    exit('Invalid record identifier.');
}
$record = $id ? module_record($key, (int) $id) : null;
if ($id && !$record) {
    flash('warning', $definition['singular'] . ' record was not found.');
    redirect($key . '/index.php');
}
$pageTitle = ($record ? 'Edit ' : 'Add ') . $definition['singular'];
$errors = $_SESSION['form_errors'] ?? [];
$values = $_SESSION['form_values'] ?? $record ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_values']);
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow"><?= e(strtoupper($definition['title'])) ?></div><h1><?= e($pageTitle) ?></h1><p>Enter the details below. Fields marked with <span class="required-mark">*</span> are required.</p></div>
    <a class="btn btn-outline-secondary" href="<?= e(url($key . '/index.php')) ?>"><i class="bi bi-arrow-left"></i> Back to <?= e($definition['title']) ?></a>
</div>
<section class="panel form-panel">
    <form method="post" action="<?= e(url('modules/save.php')) ?>" class="record-form">
        <?= csrf_field() ?>
        <input type="hidden" name="module" value="<?= e($key) ?>">
        <input type="hidden" name="id" value="<?= $id ? (int) $id : '' ?>">
        <div class="form-grid">
        <?php foreach ($definition['fields'] as $name => $field): $value = input_string($values, $name); ?>
            <div class="field-group<?= $field['type'] === 'textarea' ? ' full-width' : '' ?>">
                <label class="form-label" for="<?= e($name) ?>"><?= e($field['label']) ?><?= !empty($field['required']) ? ' <span class="required-mark">*</span>' : '' ?></label>
                <?php if ($field['type'] === 'textarea'): ?>
                    <textarea class="form-control<?= isset($errors[$name]) ? ' is-invalid' : '' ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" rows="4"<?= !empty($field['required']) ? ' required' : '' ?><?= isset($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '' ?> placeholder="Enter <?= e(strtolower($field['label'])) ?>"><?= e($value) ?></textarea>
                <?php elseif ($field['type'] === 'select' || $field['type'] === 'relation'): ?>
                    <?php $options = $field['type'] === 'relation' ? module_options($field['source']) : array_map(static fn(string $option): array => ['id' => $option, 'label' => $option], $field['options']); ?>
                    <select class="form-select<?= isset($errors[$name]) ? ' is-invalid' : '' ?>" id="<?= e($name) ?>" name="<?= e($name) ?>"<?= !empty($field['required']) ? ' required' : '' ?>>
                        <option value="">Select <?= e(strtolower($field['label'])) ?></option>
                        <?php foreach ($options as $option): ?><option value="<?= e($option['id']) ?>"<?= (string) $value === (string) $option['id'] ? ' selected' : '' ?>><?= e($option['label']) ?></option><?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input class="form-control<?= isset($errors[$name]) ? ' is-invalid' : '' ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($field['type']) ?>" value="<?= e($value) ?>"<?= !empty($field['required']) ? ' required' : '' ?><?= isset($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '' ?><?= isset($field['min']) ? ' min="' . e($field['min']) . '"' : '' ?><?= isset($field['step']) ? ' step="' . e($field['step']) . '"' : '' ?> placeholder="<?= e($field['type'] === 'number' ? '0.00' : 'Enter ' . strtolower($field['label'])) ?>">
                <?php endif; ?>
                <?php if (isset($errors[$name])): ?><div class="invalid-feedback"><?= e($errors[$name]) ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
        <div class="form-actions"><a class="btn btn-light" href="<?= e(url($key . '/index.php')) ?>">Cancel</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> <?= $record ? 'Save changes' : 'Create ' . e(strtolower($definition['singular'])) ?></button></div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
