<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_guest();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim(input_string($_POST, 'username'));
    $password = input_string($_POST, 'password');
    if (!valid_admin_username($username) || $password === '') {
        $error = 'The username or password is incorrect.';
    } else {
        $statement = db()->prepare('SELECT admin_id, username, password FROM admins WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $admin = $statement->fetch();
        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            $_SESSION['admin_id'] = (int) $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            flash('success', 'Welcome back.');
            redirect('dashboard/index.php');
        }
        $error = 'The username or password is incorrect.';
    }
}
$notices = take_flashes();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="login-brand"><span class="brand-mark"><i class="bi bi-layers"></i></span><span>Campus<span class="brand-muted">Desk</span></span></div>
        <div class="eyebrow">ADMINISTRATION PORTAL</div><h1>Welcome back</h1><p class="login-intro">Sign in to manage your student data.</p>
        <?php foreach ($notices as $notice): ?><div class="alert alert-<?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endforeach; ?>
        <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <label class="form-label" for="username">Administrator username</label>
            <input class="form-control mb-3" id="username" name="username" type="text" maxlength="50" value="<?= e(input_string($_POST, 'username')) ?>" autocomplete="username" required autofocus>
            <label class="form-label" for="password">Password</label>
            <input class="form-control mb-4" id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="btn btn-primary w-100" type="submit">Sign in <i class="bi bi-arrow-right"></i></button>
        </form>
        <div class="login-foot">Protected administration access</div>
    </main>
</body>
</html>
