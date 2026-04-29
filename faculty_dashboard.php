<?php
require_once "auth.php";
require_once __DIR__ . '/db.php';
require_once "functions.php";

bvassist_require_role(['faculty']);
$facultyUserId = bvassist_current_user_id();

date_default_timezone_set('Asia/Kolkata');

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

$totalNotices = getCount($conn, "notices");
$uploadedMaterials = getCount($conn, "handouts");
$timetableCount = 0;
$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM timetables WHERE user_id = ?");
if ($countStmt) {
    mysqli_stmt_bind_param($countStmt, "i", $facultyUserId);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $countRow = $countResult ? mysqli_fetch_assoc($countResult) : null;
    $timetableCount = (int) ($countRow['total'] ?? 0);
    mysqli_stmt_close($countStmt);
}
$facultyName = trim($_SESSION['name'] ?? 'Faculty');
$currentDateTime = date('l, F j, Y • g:i A');

$search = trim($_GET['search'] ?? '');
$type = strtolower(trim($_GET['type'] ?? 'all'));
$allowedTypes = ['all', 'notices', 'handouts', 'timetables'];
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
if ($type === 'all' || $type === 'timetables') {
    $timetableRows = [];
    if ($search === '') {
        $stmt = mysqli_prepare($conn, "SELECT * FROM timetables WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $facultyUserId);
            mysqli_stmt_execute($stmt);
            $timetableRows = bvassist_fetch_stmt_rows($stmt);
            mysqli_stmt_close($stmt);
        }
    } else {
        $searchValue = '%' . $search . '%';
        $stmt = mysqli_prepare($conn, "SELECT * FROM timetables WHERE user_id = ? AND title LIKE ? ORDER BY created_at DESC");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "is", $facultyUserId, $searchValue);
            mysqli_stmt_execute($stmt);
            $timetableRows = bvassist_fetch_stmt_rows($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    $searchBuckets[] = [
        'type' => 'Timetable',
        'rows' => $timetableRows,
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

$recentActivity = [];

foreach ([
    ['rows' => getRecent($conn, "notices", 5), 'item_type' => 'Notice'],
    ['rows' => getRecent($conn, "handouts", 5), 'item_type' => 'Handout'],
    ['rows' => (function () use ($conn, $facultyUserId) {
        $rows = [];
        $stmt = mysqli_prepare($conn, "SELECT * FROM timetables WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $facultyUserId);
            mysqli_stmt_execute($stmt);
            $rows = bvassist_fetch_stmt_rows($stmt);
            mysqli_stmt_close($stmt);
        }
        return $rows;
    })(), 'item_type' => 'Timetable'],
] as $bucket) {
    foreach ($bucket['rows'] as $row) {
        $row['item_type'] = $bucket['item_type'];
        $recentActivity[] = $row;
    }
}

usort($recentActivity, function (array $left, array $right): int {
    $leftTime = strtotime($left['created_at'] ?? '') ?: 0;
    $rightTime = strtotime($right['created_at'] ?? '') ?: 0;

    return $rightTime <=> $leftTime;
});

$recentActivity = array_slice($recentActivity, 0, 5);
$feedbackStatus = $_GET['feedback_status'] ?? '';
$feedbackMessage = $_GET['feedback_message'] ?? '';
$uploadSuccess = ($_GET['success'] ?? '') === '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Faculty Dashboard</title>
<link rel="stylesheet" href="../frontend/css/style.css">
</head>

<body class="dashboard-body faculty-dashboard-body">

<div class="dashboard-wrapper">
    <?php include "sidebar.php"; ?>

    <div class="main-content">
        <div class="top-header faculty-hero fade-in">
            <div>
                <span class="section-badge">Faculty Panel</span>
                <h1>Faculty Dashboard</h1>
                <p class="welcome-text">Welcome back, <?php echo htmlspecialchars($facultyName); ?> 👋</p>
                <p class="welcome-text">Upload notices, handouts, and timetable updates for your classes.</p>
                <p class="welcome-text faculty-datetime"><?php echo htmlspecialchars($currentDateTime); ?></p>
            </div>

            <img src="../assets/logo.png" class="uni-logo" alt="BV Assist logo">
        </div>

        <?php if ($uploadSuccess) { ?>
            <div class="feedback-alert feedback-success fade-in">
                Handout uploaded successfully.
            </div>
        <?php } ?>

        <section class="resource-search-panel fade-in">
            <div class="resource-search-head">
                <span class="section-badge">Search</span>
                <h2>Find notices, handouts, and timetables</h2>
                <p>Search your faculty resources and filter by type.</p>
            </div>

            <form method="GET" class="resource-search-form">
                <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">

                <select name="type">
                    <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="notices" <?php echo $type === 'notices' ? 'selected' : ''; ?>>Notices</option>
                    <option value="handouts" <?php echo $type === 'handouts' ? 'selected' : ''; ?>>Handouts</option>
                    <option value="timetables" <?php echo $type === 'timetables' ? 'selected' : ''; ?>>Timetables</option>
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
                                $itemHref = 'upload_notice.php';
                            } elseif (($item['item_type'] ?? '') === 'Handout') {
                                $itemHref = 'upload_handout.php';
                            } else {
                                $itemHref = 'faculty_timetable.php';
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

        <div class="faculty-stats-grid">
            <div class="summary-card faculty-summary-card faculty-summary-notices fade-in">
                <div class="summary-icon faculty-summary-icon faculty-notices-icon">🔔</div>
                <div class="summary-copy">
                    <span class="summary-label">Total Notices</span>
                    <span class="summary-value"><?php echo $totalNotices; ?></span>
                    <p>Announcements shared across the portal for students and staff.</p>
                </div>
            </div>

            <div class="summary-card faculty-summary-card faculty-summary-materials fade-in">
                <div class="summary-icon faculty-summary-icon faculty-materials-icon">📘</div>
                <div class="summary-copy">
                    <span class="summary-label">Uploaded Materials</span>
                    <span class="summary-value"><?php echo $uploadedMaterials; ?></span>
                    <p>Lecture notes, handouts, and study material ready for access.</p>
                </div>
            </div>

            <div class="summary-card faculty-summary-card faculty-summary-timetable fade-in">
                <div class="summary-icon faculty-summary-icon faculty-timetable-icon">📅</div>
                <div class="summary-copy">
                    <span class="summary-label">Timetables</span>
                    <span class="summary-value"><?php echo $timetableCount; ?></span>
                    <p>Updated class schedules available to faculty and students.</p>
                </div>
            </div>
        </div>

        <div class="faculty-actions-section fade-in">
            <div class="faculty-section-head">
                <span class="section-badge">Quick Actions</span>
                <h2>Upload and manage resources</h2>
                <p>Use these shortcuts to upload notices, handouts, and timetable updates quickly.</p>
            </div>

            <div class="faculty-action-grid">
                <div class="faculty-action-card">
                    <div class="faculty-action-icon faculty-notices-icon">🔔</div>
                    <h3>Upload Notice</h3>
                    <p>Share academic announcements and important updates with students.</p>
                    <a href="upload_notice.php" class="primary-btn faculty-action-btn">Open</a>
                </div>

                <div class="faculty-action-card">
                    <div class="faculty-action-icon faculty-materials-icon">📘</div>
                    <h3>Upload Handout</h3>
                    <p>Publish lecture notes, study material, and supporting resources.</p>
                    <a href="upload_handout.php" class="primary-btn faculty-action-btn">Upload</a>
                </div>

                <div class="faculty-action-card">
                    <div class="faculty-action-icon faculty-timetable-icon">📅</div>
                    <h3>View Timetable</h3>
                    <p>View your assigned timetable and class schedule.</p>
                    <a href="faculty_timetable.php" class="primary-btn faculty-action-btn">View</a>
                </div>
            </div>
        </div>

        <section class="feedback-section fade-in" id="feedback-section">
            <div class="feedback-section-head">
                <span class="section-badge">Feedback</span>
                <h2>Share your feedback</h2>
                <p>Send suggestions, concerns, or updates from your faculty workspace.</p>
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

        <div class="faculty-activity-section fade-in">
            <div class="faculty-section-head">
                <span class="section-badge">Recent Activity</span>
                <h2>Latest uploads</h2>
                <p>Recent notices, handouts, and timetable updates from your faculty workspace.</p>
            </div>

            <div class="faculty-activity-list">
                <?php if (!empty($recentActivity)) { ?>
                    <?php foreach ($recentActivity as $activity) { ?>
                        <div class="faculty-activity-item">
                            <div class="faculty-activity-copy">
                                <h3><?php echo htmlspecialchars($activity['title']); ?></h3>
                                <div class="faculty-activity-meta">
                                    <span class="faculty-activity-type"><?php echo htmlspecialchars($activity['item_type']); ?></span>
                                    <span class="faculty-activity-date"><?php echo !empty($activity['created_at']) ? date('M d, Y', strtotime($activity['created_at'])) : ''; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="faculty-activity-empty">
                        No recent uploads yet.
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
