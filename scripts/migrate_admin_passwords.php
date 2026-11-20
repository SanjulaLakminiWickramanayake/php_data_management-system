<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function is_existing_password_hash(string $password): bool
{
    if (password_get_info($password)['algo'] !== null) {
        return true;
    }

    return str_starts_with($password, '$')
        || preg_match('/\A(?:[a-f0-9]{32}|[a-f0-9]{40}|[a-f0-9]{64}|[a-f0-9]{128})\z/i', $password) === 1;
}

$pdo = db();
$converted = 0;
$skipped = 0;
$pdo->beginTransaction();

try {
    $admins = $pdo->query('SELECT admin_id, password FROM admins FOR UPDATE')->fetchAll();
    $update = $pdo->prepare('UPDATE admins SET password = :password WHERE admin_id = :admin_id');

    foreach ($admins as $admin) {
        if (is_existing_password_hash($admin['password'])) {
            $skipped++;
            continue;
        }

        $update->execute([
            'password' => password_hash($admin['password'], PASSWORD_DEFAULT),
            'admin_id' => $admin['admin_id'],
        ]);
        $converted++;
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

printf("Converted %d plaintext password(s); left %d hash-like value(s) unchanged.\n", $converted, $skipped);
