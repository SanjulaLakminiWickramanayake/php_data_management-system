<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_auth();
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <button class="btn btn-light d-lg-none" type="button" data-sidebar-toggle aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
            <div class="topbar-label">Administration</div>
            <div class="user-chip"><span class="avatar"><?= e(strtoupper(substr((string) ($_SESSION['admin_username'] ?? 'A'), 0, 1))) ?></span><span><?= e($_SESSION['admin_username'] ?? 'Administrator') ?></span></div>
        </header>
        <section class="content-area">
            <?php require __DIR__ . '/notifications.php'; ?>
