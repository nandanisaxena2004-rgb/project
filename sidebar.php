<?php
require_once __DIR__ . '/auth.php';

bvassist_start_session();

$currentPage = basename($_SERVER['PHP_SELF']);
$role = bvassist_current_role() ?: 'student';
$displayName = trim($_SESSION['name'] ?? 'User');

if ($displayName === '') {
    $displayName = 'User';
}

$initialsSource = preg_split('/\s+/', $displayName) ?: [];
$initials = '';
foreach ($initialsSource as $part) {
    $part = trim((string) $part);
    if ($part === '') {
        continue;
    }
    $initials .= strtoupper(substr($part, 0, 1));
    if (strlen($initials) >= 2) {
        break;
    }
}

if ($initials === '') {
    $initials = 'U';
}

$navItemsByRole = [
    'admin' => [
        ['label' => 'Dashboard', 'href' => 'admin_dashboard.php#dashboard-overview', 'match' => ['admin_dashboard.php']],
        ['label' => 'Timetable', 'href' => 'admin_dashboard.php#timetable-section', 'match' => ['admin_dashboard.php']],
        ['label' => 'Notifications', 'href' => 'admin_dashboard.php#notifications-section', 'match' => ['admin_dashboard.php']],
        ['label' => 'Handouts', 'href' => 'admin_dashboard.php#handouts-section', 'match' => ['admin_dashboard.php']],
        ['label' => 'Results', 'href' => 'admin_dashboard.php#results-section', 'match' => ['admin_dashboard.php']],
        ['label' => 'Feedback', 'href' => 'admin_dashboard.php#feedback-section', 'match' => ['admin_dashboard.php']],
    ],
    'faculty' => [
        ['label' => 'Dashboard', 'href' => 'faculty_dashboard.php', 'match' => ['faculty_dashboard.php']],
        ['label' => 'Notices', 'href' => 'student_notifications.php', 'match' => ['student_notifications.php']],
        ['label' => 'Handouts', 'href' => 'handouts.php', 'match' => ['handouts.php']],
        ['label' => 'Timetable', 'href' => 'faculty_timetable.php', 'match' => ['faculty_timetable.php']],
        ['label' => 'Upload Notice', 'href' => 'upload_notice.php', 'match' => ['upload_notice.php']],
        ['label' => 'Upload Handout', 'href' => 'upload_handout.php', 'match' => ['upload_handout.php']],
        ['label' => 'Feedback', 'href' => 'faculty_dashboard.php#feedback-section', 'match' => ['faculty_dashboard.php']],
    ],
    'student' => [
        ['label' => 'Dashboard', 'href' => 'student_dashboard.php', 'match' => ['student_dashboard.php']],
        ['label' => 'Timetable', 'href' => 'student_timetable.php', 'match' => ['student_timetable.php']],
        ['label' => 'Notifications', 'href' => 'student_notifications.php', 'match' => ['student_notifications.php']],
        ['label' => 'Handouts', 'href' => 'handouts.php', 'match' => ['handouts.php']],
        ['label' => 'Results', 'href' => 'results.php', 'match' => ['results.php']],
        ['label' => 'Feedback', 'href' => 'student_dashboard.php#feedback-section', 'match' => ['student_dashboard.php']],
    ],
];

$navItems = $navItemsByRole[$role] ?? $navItemsByRole['student'];
?>

<header class="dashboard-navbar">
    <div class="navbar-brand">
        <span class="brand-mark">BV</span>
        <span class="brand-text">BV Assist</span>
    </div>

    <nav class="navbar-links" aria-label="Primary">
        <?php foreach ($navItems as $item): ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>"
               class="<?php echo in_array($currentPage, $item['match'], true) ? 'active' : ''; ?>">
               <?php echo htmlspecialchars($item['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="navbar-user">
        <div class="navbar-avatar" aria-hidden="true"><?php echo htmlspecialchars($initials); ?></div>
        <div class="navbar-user-meta">
            <span class="navbar-user-label">Signed in as</span>
            <span class="navbar-user-name"><?php echo htmlspecialchars($displayName); ?></span>
        </div>

        <a href="logout.php" class="navbar-logout">Logout</a>
    </div>
</header>
