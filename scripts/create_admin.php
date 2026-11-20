<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

if (PHP_SAPI === 'cli') {
    http_response_code(404);
    exit;
}

$error = '';
$username = '';
$setupDisabled = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
$message = $setupDisabled ? 'Administrator setup is already complete and has been disabled.' : '';
if ($setupDisabled) {
    http_response_code(410);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($setupDisabled) {
        http_response_code(410);
        $message = 'Administrator setup is already complete and has been disabled.';
    } else {
        $username = trim(input_string($_POST, 'username'));
        $password = input_string($_POST, 'password');
        $confirmPassword = input_string($_POST, 'confirm_password');

        if (!valid_admin_username($username)) {
            $error = 'Use 1-50 letters, numbers, periods, underscores, or hyphens for the username.';
        } elseif (strlen($password) < 12 || strlen($password) > 72) {
            $error = 'Use a password between 12 and 72 bytes long.';
        } elseif (!hash_equals($password, $confirmPassword)) {
            $error = 'The passwords do not match.';
        } else {
            $lockAcquired = (int) db()->query("SELECT GET_LOCK('php_data_management_admin_setup', 10)")->fetchColumn() === 1;
            if (!$lockAcquired) {
                $error = 'Administrator setup is busy. Please try again.';
            } else {
                try {
                    $setupDisabled = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
                    if ($setupDisabled) {
                        http_response_code(410);
                        $message = 'Administrator setup is already complete and has been disabled.';
                    } else {
                        $statement = db()->prepare('INSERT INTO admins (username, password) VALUES (:username, :password)');
                        try {
                            $statement->execute([
                                'username' => $username,
                                'password' => password_hash($password, PASSWORD_DEFAULT),
                            ]);
                            $setupDisabled = true;
                            $message = 'Administrator account created. Setup is now disabled; you can sign in.';
                        } catch (PDOException $exception) {
                            if ($exception->getCode() !== '23000') {
                                throw $exception;
                            }
                            $setupDisabled = true;
                            http_response_code(410);
                            $message = 'Administrator setup is already complete and has been disabled.';
                        }
                    }
                } finally {
                    db()->query("SELECT RELEASE_LOCK('php_data_management_admin_setup')");
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator setup | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="eyebrow">ONE-TIME ADMINISTRATOR SETUP</div>
        <h1>Create administrator</h1>
        <p class="login-intro">Create the first account for this application.</p>
        <?php if ($message !== ''): ?><div class="alert alert-<?= $setupDisabled ? 'info' : 'success' ?>" role="status"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if (!$setupDisabled): ?>
            <form method="post">
                <?= csrf_field() ?>
                <label class="form-label" for="username">Username</label>
                <input class="form-control mb-3" id="username" name="username" type="text" maxlength="50" value="<?= e($username) ?>" autocomplete="username" required autofocus>
                <label class="form-label" for="password">Password</label>
                <input class="form-control mb-3" id="password" name="password" type="password" autocomplete="new-password" required>
                <label class="form-label" for="confirm_password">Confirm password</label>
                <input class="form-control mb-4" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                <button class="btn btn-primary w-100" type="submit">Create administrator</button>
            </form>
        <?php else: ?>
            <p class="mb-0"><a href="<?= e(url('auth/login.php')) ?>">Go to sign in</a></p>
        <?php endif; ?>
    </main>
</body>
</html>
