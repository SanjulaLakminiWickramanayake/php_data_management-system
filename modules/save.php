<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/modules.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();
$key = input_string($_POST, 'module');
$definition = module_definition($key);
$idValue = $_POST['id'] ?? '';
$id = $idValue === '' ? null : (is_scalar($idValue) ? filter_var($idValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false);
if ($idValue !== '' && $id === false) {
    flash('danger', 'Invalid record identifier.');
    redirect($key . '/index.php');
}
if ($id !== null && !module_record($key, (int) $id)) {
    flash('warning', $definition['singular'] . ' record was not found.');
    redirect($key . '/index.php');
}
[$data, $errors] = validate_module_input($definition, $_POST, $id === null ? null : (int) $id);
if ($errors) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_values'] = array_intersect_key($_POST, $definition['fields']);
    flash('danger', 'Please correct the highlighted fields.');
    redirect('modules/form.php?module=' . rawurlencode($key) . ($id ? '&id=' . (int) $id : ''));
}

try {
    if ($id === null) {
        module_insert($key, $data);
        flash('success', $definition['singular'] . ' created successfully.');
    } else {
        module_update($key, (int) $id, $data);
        flash('success', $definition['singular'] . ' updated successfully.');
    }
} catch (PDOException $exception) {
    error_log('Record save failed for ' . $key . ': ' . $exception->getMessage());
    flash('danger', $exception->getCode() === '23000'
        ? 'This record conflicts with existing data or is still referenced by related records.'
        : 'The record could not be saved. Please try again.');
    $_SESSION['form_values'] = array_intersect_key($_POST, $definition['fields']);
    redirect('modules/form.php?module=' . rawurlencode($key) . ($id ? '&id=' . (int) $id : ''));
}
redirect($key . '/index.php');
