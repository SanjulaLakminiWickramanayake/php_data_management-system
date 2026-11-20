<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('dashboard/index.php');
}
redirect('auth/login.php');