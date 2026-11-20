<?php
$currentPage = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if (isset($_GET['module']) && in_array($_GET['module'], ['students', 'courses', 'instructors', 'enrollments', 'payments'], true)) {
    $currentPage = $_GET['module'];
}
$navigation = [
    ['Dashboard', 'dashboard/index.php', 'bi-grid-1x2'],
    ['Students', 'students/index.php', 'bi-people'],
    ['Courses', 'courses/index.php', 'bi-journal-bookmark'],
    ['Instructors', 'instructors/index.php', 'bi-person-workspace'],
    ['Enrollments', 'enrollments/index.php', 'bi-card-checklist'],
    ['Payments', 'payments/index.php', 'bi-credit-card'],
];
?>
<aside class="sidebar" data-sidebar>
    <a class="brand" href="<?= e(url('dashboard/index.php')) ?>"><span class="brand-mark"><i class="bi bi-layers"></i></span><span>Campus<span class="brand-muted">Desk</span></span></a>
    <div class="sidebar-caption">WORKSPACE</div>
    <nav class="side-nav">
        <?php foreach ($navigation as [$label, $path, $icon]): ?>
            <?php $active = $currentPage === strtok($path, '/'); ?>
            <a class="side-link<?= $active ? ' active' : '' ?>" href="<?= e(url($path)) ?>"><i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span></a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom">
        <div class="sidebar-caption">ACCOUNT</div>
        <form method="post" action="<?= e(url('auth/logout.php')) ?>"><?= csrf_field() ?><button class="side-link signout-link" type="submit"><i class="bi bi-box-arrow-right"></i><span>Sign out</span></button></form>
        <div class="sidebar-footer">PHP Data Management<br><span>Administration portal</span></div>
    </div>
</aside>
