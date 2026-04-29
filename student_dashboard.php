<?php
require_once "auth.php";

bvassist_require_role(['student']);

include "db.php";
require_once "functions.php";

function format_notice_excerpt($text, $limit = 110) {
    $clean = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
    if ($clean === '') {
        return 'New notice available in your dashboard.';
    }

    if (function_exists('mb_strlen') && mb_strlen($clean) > $limit) {
        return rtrim(mb_substr($clean, 0, $limit - 1)) . '...';
    }

    if (!function_exists('mb_strlen') && strlen($clean) > $limit) {
        return rtrim(substr($clean, 0, $limit - 1)) . '...';
    }

    return $clean;
}

$studentName = trim($_SESSION['name'] ?? 'Student');
$totalNotices = getCount($conn, "notices");
$availableHandouts = getCount($conn, "handouts");
$resultsPublished = getCount($conn, "results");

$search = trim($_GET['search'] ?? '');
$type = strtolower(trim($_GET['type'] ?? 'all'));
$allowedTypes = ['all', 'notices', 'handouts', 'results'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'all';
}

$searchBuckets = [];
if ($type === 'all' || $type === 'notices') {
    $searchBuckets[] = [
        'type' => 'Notice',
        'rows' => searchRecords($conn, "notices", $search, ['title', 'content']),
    ];
}
if ($type === 'all' || $type === 'handouts') {
    $searchBuckets[] = [
        'type' => 'Handout',
        'rows' => searchRecords($conn, "handouts", $search, ['title']),
    ];
}
if ($type === 'all' || $type === 'results') {
    $searchBuckets[] = [
        'type' => 'Result',
        'rows' => searchRecords($conn, "results", $search, ['title']),
    ];
}

$searchResults = [];
foreach ($searchBuckets as $bucket) {
    foreach ($bucket['rows'] as $row) {
        $row['item_type'] = $bucket['type'];
        $searchResults[] = $row;
    }
}

usort($searchResults, function (array $left, array $right): int {
    $leftTime = strtotime($left['created_at'] ?? '') ?: 0;
    $rightTime = strtotime($right['created_at'] ?? '') ?: 0;

    return $rightTime <=> $leftTime;
});

$recentNotices = getRecent($conn, "notices", 5);
foreach ($recentNotices as &$notice) {
    $notice['notice_date'] = $notice['created_at'] ?? '';
}
unset($notice);
$feedbackStatus = $_GET['feedback_status'] ?? '';
$feedbackMessage = $_GET['feedback_message'] ?? '';

// PREVENT BACK CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BVAssist | Dashboard</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../frontend/css/style.css">

    <!-- Google Font (Premium look) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body class="dashboard-body student-dashboard-body">

<div class="dashboard-wrapper">

    <?php include "sidebar.php"; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- TOP HEADER -->
        <div class="top-header student-hero fade-in">
            <div>
                <span class="section-badge">Student Portal</span>
                <h1>Welcome, <?php echo htmlspecialchars($studentName); ?></h1>
                <p class="welcome-text">Access your academic resources in one place</p>
            </div>

            <!-- LOGO FIX -->
            <img src="../assets/logo.png" class="uni-logo" alt="Logo">
        </div>

        <section class="resource-search-panel fade-in">
            <div class="resource-search-head">
                <span class="section-badge">Search</span>
                <h2>Find notices, handouts, and results</h2>
                <p>Search across your accessible academic resources and filter by type.</p>
            </div>

            <form method="GET" class="resource-search-form">
                <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">

                <select name="type">
                    <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="notices" <?php echo $type === 'notices' ? 'selected' : ''; ?>>Notices</option>
                    <option value="handouts" <?php echo $type === 'handouts' ? 'selected' : ''; ?>>Handouts</option>
                    <option value="results" <?php echo $type === 'results' ? 'selected' : ''; ?>>Results</option>
                </select>

                <button type="submit">Search</button>
            </form>

            <div class="resource-search-results">
                <?php if (!empty($searchResults)) { ?>
                    <?php foreach ($searchResults as $item) { ?>
                        <?php
                            $itemTitle = trim($item['title'] ?? 'Untitled item');
                            $itemDate = !empty($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : '';
                            $itemExcerpt = '';

                            if (($item['item_type'] ?? '') === 'Notice') {
                                $noticeText = trim((string)($item['content'] ?? ''));
                                $itemExcerpt = $noticeText !== ''
                                    ? format_notice_excerpt($noticeText, 120)
                                    : format_notice_excerpt($itemTitle . ' is available now.', 120);
                            } else {
                                $itemExcerpt = trim((string)($item['file_path'] ?? $item['file_name'] ?? ''));
                                if ($itemExcerpt === '') {
                                    $itemExcerpt = 'Open this resource to view the latest file.';
                                }
                            }

                            $itemHref = '';
                            if (!empty($item['file_path'])) {
                                $itemHref = bvassist_upload_url_from_path($item['file_path']);
                            } elseif (($item['item_type'] ?? '') === 'Notice') {
                                $itemHref = 'student_notifications.php';
                            } elseif (($item['item_type'] ?? '') === 'Handout') {
                                $itemHref = 'handouts.php';
                            } else {
                                $itemHref = 'results.php';
                            }
                        ?>
                        <article class="resource-result-card">
                            <div class="resource-result-top">
                                <span class="resource-result-badge"><?php echo htmlspecialchars($item['item_type']); ?></span>
                                <time><?php echo htmlspecialchars($itemDate); ?></time>
                            </div>
                            <h3><?php echo htmlspecialchars($itemTitle); ?></h3>
                            <p><?php echo htmlspecialchars($itemExcerpt); ?></p>
                            <a href="<?php echo htmlspecialchars($itemHref); ?>" class="resource-result-link" target="_blank">View</a>
                        </article>
                    <?php } ?>
                <?php } else { ?>
                    <div class="resource-result-empty">
                        No results found.
                    </div>
                <?php } ?>
            </div>
        </section>

        <div class="summary-grid student-stats-grid">
            <div class="summary-card student-summary-card student-summary-notices fade-in">
                <div class="summary-icon student-summary-icon student-notices-icon">🔔</div>
                <div class="summary-copy">
                    <span class="summary-label">Total Notices</span>
                    <span class="summary-value"><?php echo $totalNotices; ?></span>
                    <p>Important announcements and updates available to you.</p>
                </div>
            </div>

            <div class="summary-card student-summary-card student-summary-handouts fade-in">
                <div class="summary-icon student-summary-icon student-handouts-icon">📘</div>
                <div class="summary-copy">
                    <span class="summary-label">Available Handouts</span>
                    <span class="summary-value"><?php echo $availableHandouts; ?></span>
                    <p>Lecture notes and study material ready to view or download.</p>
                </div>
            </div>

            <div class="summary-card student-summary-card student-summary-results fade-in">
                <div class="summary-icon student-summary-icon student-results-icon">📊</div>
                <div class="summary-copy">
                    <span class="summary-label">Results Published</span>
                    <span class="summary-value"><?php echo $resultsPublished; ?></span>
                    <p>Published result records currently accessible in the portal.</p>
                </div>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="dashboard-container student-actions-grid">

            <div class="student-action-card fade-in">
                <div class="student-action-icon timetable-action">📅</div>
                <h3>View Timetable</h3>
                <p>Check your class schedule and stay updated on daily academic timings.</p>
                <a href="student_timetable.php" class="student-action-btn">Open</a>
            </div>

            <div class="student-action-card fade-in">
                <div class="student-action-icon notifications-action">🔔</div>
                <h3>View Notifications</h3>
                <p>Read the latest notices, updates, and important announcements.</p>
                <a href="student_notifications.php" class="student-action-btn">View</a>
            </div>

            <div class="student-action-card fade-in">
                <div class="student-action-icon handouts-action">📘</div>
                <h3>Download Handouts</h3>
                <p>Access and download study materials shared by your faculty.</p>
                <a href="handouts.php" class="student-action-btn">Open</a>
            </div>

            <div class="student-action-card fade-in">
                <div class="student-action-icon results-action">📊</div>
                <h3>View Results</h3>
                <p>Review your published results and track your academic progress.</p>
                <a href="results.php" class="student-action-btn">View</a>
            </div>

        </div>

        <section class="feedback-section fade-in" id="feedback-section">
            <div class="feedback-section-head">
                <span class="section-badge">Feedback</span>
                <h2>Share your feedback</h2>
                <p>Send your suggestions, questions, or concerns to the administration.</p>
            </div>

            <?php if ($feedbackMessage !== '') { ?>
                <div class="feedback-alert <?php echo $feedbackStatus === 'success' ? 'feedback-success' : 'feedback-error'; ?>">
                    <?php echo htmlspecialchars($feedbackMessage); ?>
                </div>
            <?php } ?>

            <div class="feedback-card">
                <form method="POST" action="submit_feedback.php" class="feedback-form">
                    <label for="feedback-message">Message</label>
                    <textarea id="feedback-message" name="message" rows="5" placeholder="Write your feedback here..." required></textarea>
                    <button type="submit" class="primary-btn">Submit Feedback</button>
                </form>
            </div>
        </section>

        <section class="recent-notices-panel fade-in">
            <div class="recent-notices-header">
                <div>
                    <span class="section-badge">Recent Updates</span>
                    <h2>Recent Notifications</h2>
                    <p>Catch the latest notices without opening the full notification page.</p>
                </div>

                <a href="student_notifications.php" class="recent-view-all-btn">View All</a>
            </div>

            <div class="recent-notices-list">
                <?php if (!empty($recentNotices)) { ?>
                    <?php foreach ($recentNotices as $notice) { ?>
                        <?php
                            $noticeTitle = trim($notice['title'] ?? 'Untitled notice');
                            $noticeMessage = $notice['message'] ?? '';
                            $noticeDateValue = $notice['notice_date'] ?? '';
                            $noticeDate = $noticeDateValue ? date('M j, Y', strtotime($noticeDateValue)) : 'Recently posted';
                            $shortMessage = $noticeMessage !== ''
                                ? format_notice_excerpt($noticeMessage, 120)
                                : format_notice_excerpt(($noticeTitle !== '' ? $noticeTitle . ' is available now.' : ''), 120);
                        ?>
                        <article class="recent-notice-card">
                            <div class="recent-notice-icon">🔔</div>
                            <div class="recent-notice-body">
                                <div class="recent-notice-top">
                                    <h3><?php echo htmlspecialchars($noticeTitle); ?></h3>
                                    <time><?php echo htmlspecialchars($noticeDate); ?></time>
                                </div>
                                <p><?php echo htmlspecialchars($shortMessage); ?></p>
                            </div>
                        </article>
                    <?php } ?>
                <?php } else { ?>
                    <div class="recent-notice-empty">
                        No recent notifications found.
                    </div>
                <?php } ?>
            </div>
        </section>

    </div>

</div>

</body>
</html>
