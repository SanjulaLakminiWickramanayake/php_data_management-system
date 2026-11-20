<?php
declare(strict_types=1);

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_exception_handler(static function (Throwable $exception): void {
    error_log((string) $exception);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Application error: ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Application error</title><body><h1>Something went wrong</h1><p>The request could not be completed. Check the application setup and try again.</p></body></html>';
});

define('APP_NAME', 'PHP Data Management');
$baseUrl = getenv('APP_BASE_URL');
if ($baseUrl === false) {
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $applicationRoot = realpath(__DIR__ . '/..');
    $baseUrl = '';
    if ($documentRoot !== false && $applicationRoot !== false && str_starts_with(strtolower($applicationRoot), strtolower($documentRoot . DIRECTORY_SEPARATOR))) {
        $relativePath = substr($applicationRoot, strlen($documentRoot) + 1);
        $baseUrl = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    }
}
$baseUrl = trim($baseUrl, '/');
define('APP_BASE_URL', $baseUrl === '' ? '' : '/' . $baseUrl);
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'php_data_management');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';
