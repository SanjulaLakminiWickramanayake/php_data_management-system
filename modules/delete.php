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
$idValue = $_POST['id'] ?? null;
$id = is_scalar($idValue) ? filter_var($idValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
if ($id === false || $id === null) {
    flash('danger', 'Invalid record identifier.');
    redirect($key . '/index.php');
}
try {
    module_delete($key, (int) $id);
    flash('success', $definition['singular'] . ' deleted successfully.');
} catch (PDOException $exception) {
    error_log('Record deletion failed for ' . $key . ': ' . $exception->getMessage());
    flash('danger', $exception->getCode() === '23000'
        ? 'This record is linked to other records and cannot be deleted until those links are removed.'
        : 'The record could not be deleted. Please try again.');
}
redirect($key . '/index.php');
