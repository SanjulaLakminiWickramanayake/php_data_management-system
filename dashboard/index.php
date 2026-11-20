<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_auth();
$counts = [];
foreach ([
    'Students' => 'students',
    'Courses' => 'courses',
    'Instructors' => 'instructors',
    'Enrollments' => 'enrollments',
    'Payments' => 'payments',
] as $label => $table) {
    $counts[$label] = (int) db()->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
}
$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow">OVERVIEW</div><h1>Dashboard</h1><p>Here is an overview of your student management system.</p></div>
    <span class="date-pill"><i class="bi bi-calendar3"></i> <?= e(date('F j, Y')) ?></span>
</div>
<div class="stats-grid">
    <?php foreach ([
        'Students' => ['bi-people', 'blue', 'students/index.php', 'Registered learners'],
        'Courses' => ['bi-journal-bookmark', 'violet', 'courses/index.php', 'Available programs'],
        'Instructors' => ['bi-person-workspace', 'amber', 'instructors/index.php', 'Teaching staff'],
        'Enrollments' => ['bi-card-checklist', 'green', 'enrollments/index.php', 'Course registrations'],
        'Payments' => ['bi-credit-card', 'rose', 'payments/index.php', 'Payment records'],
    ] as $label => [$icon, $color, $path, $caption]): ?>
        <a class="stat-card" href="<?= e(url($path)) ?>">
            <div class="stat-icon <?= e($color) ?>"><i class="bi <?= e($icon) ?>"></i></div>
            <div class="stat-label"><?= e($label) ?></div><div class="stat-number"><?= number_format($counts[$label]) ?></div>
            <div class="stat-caption"><?= e($caption) ?><i class="bi bi-arrow-up-right"></i></div>
        </a>
    <?php endforeach; ?>
</div>
<section class="welcome-panel">
    <div><div class="eyebrow">YOUR WORKSPACE</div><h2>Manage your institution in one place</h2><p>Keep student records, courses, enrollments, instructors, and payments organized and up to date.</p></div>
    <a class="btn btn-primary" href="<?= e(url('students/index.php')) ?>">Go to students <i class="bi bi-arrow-right"></i></a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
