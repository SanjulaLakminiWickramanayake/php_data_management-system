<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function valid_admin_username(string $username): bool
{
    return preg_match('/\A[A-Za-z0-9._-]{1,50}\z/', $username) === 1;
}

function require_auth(): void
{
    if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
        flash('warning', 'Please sign in to continue.');
        redirect('auth/login.php');
    }
}

function require_guest(): void
{
    if (!empty($_SESSION['admin_id']) && !empty($_SESSION['admin_username'])) {
        redirect('dashboard/index.php');
    }
}
