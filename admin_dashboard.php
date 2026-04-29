<?php
require_once "auth.php";
include "db.php";
require_once "functions.php";
require_once "upload_utils.php";

bvassist_require_role(['admin']);

$handouts = getAll($conn, "handouts");
$timetable = getAll($conn, "timetables");
$results = getAll($conn, "results");
$notices = getAll($conn, "notices");
$feedbackItems = getAll($conn, "feedback");
$assignableUsers = [];
$assignableUsersResult = mysqli_query(
    $conn,
    "SELECT id, name, email, LOWER(role) AS role FROM users WHERE LOWER(role) IN ('student', 'faculty') ORDER BY role ASC, name ASC, email ASC"
);

while ($assignableUsersResult && ($userRow = mysqli_fetch_assoc($assignableUsersResult))) {
    $assignableUsers[] = $userRow;
}

$feedbackHasModernSchema = bvassist_has_column($conn, 'feedback', 'email')
    && bvassist_has_column($conn, 'feedback', 'role')
    && bvassist_has_column($conn, 'feedback', 'status');

$handoutCount = getCount($conn, "handouts");
$timetableCount = getCount($conn, "timetables");
$resultCount = getCount($conn, "results");
$noticeCount = getCount($conn, "notices");
$userCount = getCount($conn, "users");
$totalUploads = $handoutCount + $timetableCount + $resultCount + $noticeCount;

$latestNoticeRow = getRecent($conn, "notices", 1)[0] ?? null;
$latestHandoutRow = getRecent($conn, "handouts", 1)[0] ?? null;
$latestResultRow = getRecent($conn, "results", 1)[0] ?? null;

$recentActivityTitle = $latestNoticeRow['title']
    ?? $latestHandoutRow['title']
    ?? $latestResultRow['title']
    ?? 'No recent uploads yet';

$uploadStatus = $_GET['upload_status'] ?? '';
$uploadMessage = $_GET['upload_message'] ?? '';
$feedbackStatus = $_GET['feedback_status'] ?? '';
$feedbackMessage = $_GET['feedback_message'] ?? '';

function display_upload_url(array $row): string {
    $path = $row['file_path'] ?? '';
    if ($path === '' && !empty($row['file_name'])) {
        $path = 'uploads/' . $row['file_name'];
    }
    $url = bvassist_upload_url_from_path($path);
    return $url !== '' ? $url : '#';
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../frontend/css/style.css">
</head>

<body class="dashboard-body">

<div class="dashboard-wrapper">

<?php include "sidebar.php"; ?>

<div class="main-content">

<!-- HEADER -->
<div class="top-header admin-hero" id="dashboard-overview">
    <div>
        <span class="section-badge">Admin Panel</span>
        <h1>Admin Dashboard</h1>
        <p class="welcome-text">Manage uploads, organize academic resources, and keep students updated from one clean workspace.</p>
    </div>
</div>

<div class="summary-grid">
    <div class="summary-card summary-card-uploads fade-in">
        <div class="summary-icon uploads-summary">⬆</div>
        <div class="summary-copy">
            <span class="summary-label">Total Uploads</span>
            <span class="summary-value"><?php echo $totalUploads; ?></span>
            <p><?php echo $handoutCount; ?> handouts, <?php echo $timetableCount; ?> timetables, <?php echo $resultCount; ?> results, <?php echo $noticeCount; ?> notices</p>
        </div>
    </div>

    <div class="summary-card summary-card-users fade-in">
        <div class="summary-icon users-summary">👥</div>
        <div class="summary-copy">
            <span class="summary-label">Total Users</span>
            <span class="summary-value"><?php echo $userCount; ?></span>
            <p>Registered users with access to the BV Assist portal.</p>
        </div>
    </div>

    <div class="summary-card summary-card-activity fade-in">
        <div class="summary-icon activity-summary">⚡</div>
        <div class="summary-copy">
            <span class="summary-label">Recent Activity</span>
            <span class="summary-value summary-value-text"><?php echo htmlspecialchars($recentActivityTitle); ?></span>
            <p>Latest uploaded content currently visible across the dashboard.</p>
        </div>
    </div>
</div>

<?php if ($uploadMessage !== '') { ?>
<div class="dash-card" style="margin-bottom: 18px; border-left: 4px solid <?php echo $uploadStatus === 'success' ? '#2e8b57' : '#c0392b'; ?>;">
    <strong><?php echo $uploadStatus === 'success' ? 'Success' : 'Error'; ?>:</strong>
    <?php echo htmlspecialchars($uploadMessage); ?>
</div>
<?php } ?>

<!-- CARDS -->
<div class="dashboard-container admin-card-grid">

    <!-- HANDOUT -->
    <div class="dash-card upload-card" id="handouts-section">
        <div class="card-icon admin-icon handout-icon">📘</div>
        <div class="card-copy">
            <h3>Upload Handouts</h3>
            <p>Add lecture notes, study materials, or reference documents for students.</p>
        </div>
        <form action="upload_handout.php" method="POST" enctype="multipart/form-data">
            <div class="form-field">
                <label for="handout-title">Title</label>
                <input id="handout-title" type="text" name="title" placeholder="Enter handout title" required>
            </div>

            <div class="form-field">
                <label for="handout-file">Select File</label>
                <div class="file-upload-row">
                    <label for="handout-file" class="file-upload-btn">Choose file</label>
                    <span class="file-upload-name" data-default-text="No file selected">No file selected</span>
                </div>
                <input id="handout-file" class="file-upload-input" type="file" name="file" accept="application/pdf,image/*" required>
            </div>

            <button type="submit" class="primary-btn">Upload Handout</button>
        </form>
    </div>

    <!-- TIMETABLE -->
    <div class="dash-card upload-card" id="timetable-section">
        <div class="card-icon admin-icon timetable-icon">📅</div>
        <div class="card-copy">
            <h3>Upload Timetable</h3>
            <p>Share updated schedules for students and faculty with the correct audience.</p>
        </div>
        <form action="upload_timetable.php" method="POST" enctype="multipart/form-data">
            <div class="form-field">
                <label for="timetable-title">Title</label>
                <input id="timetable-title" type="text" name="title" placeholder="Enter timetable title" required>
            </div>

            <div class="form-field">
                <label for="timetable-type">Type</label>
                <select id="timetable-type" name="type" required>
                    <option value="">Select type</option>
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                </select>
            </div>

            <div class="form-field">
                <label for="timetable-assign-to">Assign To</label>
                <select id="timetable-assign-to" name="assign_to" required>
                    <option value="">Select user</option>
                    <?php foreach ($assignableUsers as $assignableUser) { ?>
                        <option
                            value="<?php echo (int) $assignableUser['id']; ?>"
                            data-role="<?php echo htmlspecialchars($assignableUser['role']); ?>"
                        >
                            <?php echo htmlspecialchars($assignableUser['name'] . ' (' . $assignableUser['role'] . ' - ' . $assignableUser['email'] . ')'); ?>
                        </option>
                    <?php } ?>
                </select>
                <p class="upload-helper-text">Choose the exact student or faculty member who should own this timetable.</p>
            </div>

            <div class="form-field">
                <label for="timetable-file">Select File</label>
                <div class="file-upload-row">
                    <label for="timetable-file" class="file-upload-btn">Choose file</label>
                    <span class="file-upload-name" data-default-text="No file selected">No file selected</span>
                </div>
                <input id="timetable-file" class="file-upload-input" type="file" name="file" accept="application/pdf,image/*" required>
                <p class="upload-helper-text">AI extraction runs automatically for supported timetable PDFs and images when <code>OPENAI_API_KEY</code> is configured.</p>
            </div>

            <button type="submit" class="primary-btn">Upload Timetable</button>
        </form>
    </div>

    <!-- NOTIFICATIONS -->
    <div class="dash-card upload-card" id="notifications-section">
        <div class="card-icon admin-icon notice-icon">🔔</div>
        <div class="card-copy">
            <h3>Upload Notifications</h3>
            <p>Create important notices and announcements for students in a few clicks.</p>
        </div>
        <form action="upload_notice.php" method="POST" enctype="multipart/form-data">
            <div class="form-field">
                <label for="notice-title">Title</label>
                <input id="notice-title" type="text" name="title" placeholder="Enter notification title" required>
            </div>

            <div class="form-field">
                <label for="notice-message">Message</label>
                <textarea id="notice-message" name="message" placeholder="Write the notification message..." rows="4"></textarea>
            </div>

            <div class="form-field">
                <label for="notice-file">Select File</label>
                <div class="file-upload-row">
                    <label for="notice-file" class="file-upload-btn">Choose file</label>
                    <span class="file-upload-name" data-default-text="No file selected">No file selected</span>
                </div>
                <input id="notice-file" class="file-upload-input" type="file" name="file" accept="application/pdf,image/*" required>
            </div>

            <button type="submit" class="primary-btn">Upload Notification</button>
        </form>
    </div>

    <!-- RESULTS -->
    <div class="dash-card upload-card" id="results-section">
        <div class="card-icon admin-icon result-icon">📊</div>
        <div class="card-copy">
            <h3>Upload Results</h3>
            <p>Manage result uploads and keep performance records ready for access.</p>
        </div>
        <form action="upload_results.php" method="POST" enctype="multipart/form-data">
            <div class="form-field">
                <label for="result-title">Title</label>
                <input id="result-title" type="text" name="title" placeholder="Enter result title" required>
            </div>

            <div class="form-field">
                <label for="result-file">Select File</label>
                <div class="file-upload-row">
                    <label for="result-file" class="file-upload-btn">Choose file</label>
                    <span class="file-upload-name" data-default-text="No file selected">No file selected</span>
                </div>
                <input id="result-file" class="file-upload-input" type="file" name="file" accept="application/pdf,image/*" required>
            </div>

            <button type="submit" class="primary-btn">Upload Result</button>
        </form>
    </div>

</div>

<!-- FEEDBACK SECTION -->
<section class="feedback-section admin-feedback-section fade-in" id="feedback-section">
    <div class="feedback-section-head">
        <span class="section-badge">User Feedback</span>
        <h2>User Feedback</h2>
        <p>Review and manage feedback submitted by students and faculty.</p>
    </div>

    <?php if ($feedbackMessage !== '') { ?>
        <div class="feedback-alert <?php echo $feedbackStatus === 'success' ? 'feedback-success' : 'feedback-error'; ?>">
            <?php echo htmlspecialchars($feedbackMessage); ?>
        </div>
    <?php } ?>

    <div class="feedback-admin-wrap">
        <?php if (!empty($feedbackItems)) { ?>
            <div class="feedback-table">
                <div class="feedback-table-head">
                    <span>Email</span>
                    <span>Role</span>
                    <span>Message</span>
                    <span>Date</span>
                    <span>Status</span>
                    <span>Actions</span>
                </div>

                <?php foreach ($feedbackItems as $feedback) { ?>
                    <?php $feedbackDate = $feedback['created_at'] ?? $feedback['submitted_at'] ?? ''; ?>
                    <?php $feedbackStatusValue = $feedback['status'] ?? 'pending'; ?>
                    <div class="feedback-table-row">
                        <span><?php echo htmlspecialchars($feedback['email'] ?? ($feedback['student_email'] ?? '')); ?></span>
                        <span><?php echo htmlspecialchars(ucfirst($feedback['role'] ?? 'student')); ?></span>
                        <span><?php echo htmlspecialchars($feedback['message'] ?? ''); ?></span>
                        <span><?php echo !empty($feedbackDate) ? htmlspecialchars(date('M d, Y', strtotime($feedbackDate))) : ''; ?></span>
                        <span>
                            <span class="feedback-status-pill feedback-status-<?php echo htmlspecialchars($feedbackStatusValue); ?>">
                                <?php echo htmlspecialchars(ucfirst($feedbackStatusValue)); ?>
                            </span>
                        </span>
                        <span class="feedback-actions">
                            <?php if ($feedbackHasModernSchema && $feedbackStatusValue !== 'resolved') { ?>
                                <form method="POST" action="resolve_feedback.php">
                                    <input type="hidden" name="id" value="<?php echo (int) $feedback['id']; ?>">
                                    <button type="submit" class="feedback-action-btn feedback-resolve-btn">Resolve</button>
                                </form>
                            <?php } ?>
                            <form method="POST" action="delete_feedback.php" onsubmit="return confirm('Delete this feedback?');">
                                <input type="hidden" name="id" value="<?php echo (int) $feedback['id']; ?>">
                                <button type="submit" class="feedback-action-btn feedback-delete-btn">Delete</button>
                            </form>
                        </span>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="resource-result-empty">No feedback yet.</div>
        <?php } ?>
    </div>
</section>

<!-- FILES SECTION -->
<div class="uploads-panel">
    <div class="uploads-header">
        <div>
            <h2>Uploaded Files</h2>
            <p>Review existing resources and remove outdated files when needed.</p>
        </div>
    </div>

    <div class="uploads-table">
        <div class="uploads-table-head">
            <span>Resource</span>
            <span>Type</span>
            <span>Actions</span>
        </div>

        <?php foreach ($handouts as $row) { ?>
        <div class="upload-row">
            <div class="upload-file-meta">
                <span class="row-icon">📘</span>
                <div>
                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                    <small><?php echo htmlspecialchars($row['file_path'] ?? $row['file_name'] ?? ''); ?></small>
                </div>
            </div>
            <span class="type-pill">Handout</span>
            <div class="file-actions">
                <a href="<?php echo htmlspecialchars(display_upload_url($row)); ?>" target="_blank">View</a>
                <a href="delete.php?id=<?php echo $row['id']; ?>" class="delete-btn">Delete</a>
            </div>
        </div>
        <?php } ?>

        <?php foreach ($timetable as $row) { ?>
        <div class="upload-row">
            <div class="upload-file-meta">
                <span class="row-icon">📅</span>
                <div>
                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                    <small><?php echo htmlspecialchars($row['file_path'] ?? $row['file_name'] ?? ''); ?></small>
                </div>
            </div>
            <span class="type-pill"><?php echo !empty($row['type']) ? ucfirst($row['type']) . ' Timetable' : 'Timetable'; ?></span>
            <div class="file-actions">
                <a href="<?php echo htmlspecialchars(display_upload_url($row)); ?>" target="_blank">View</a>
            </div>
        </div>
        <?php } ?>

        <?php foreach ($results as $row) { ?>
        <div class="upload-row">
            <div class="upload-file-meta">
                <span class="row-icon">📊</span>
                <div>
                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                    <small><?php echo htmlspecialchars($row['file_path'] ?? $row['file_name'] ?? ''); ?></small>
                </div>
            </div>
            <span class="type-pill">Result</span>
            <div class="file-actions">
                <a href="<?php echo htmlspecialchars(display_upload_url($row)); ?>" target="_blank">View</a>
            </div>
        </div>
        <?php } ?>

        <?php foreach ($notices as $row) { ?>
        <div class="upload-row">
            <div class="upload-file-meta">
                <span class="row-icon">🔔</span>
                <div>
                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                    <small><?php echo htmlspecialchars($row['file_path'] ?? $row['file_name'] ?? ''); ?></small>
                </div>
            </div>
            <span class="type-pill">Notice</span>
            <div class="file-actions">
                <a href="<?php echo htmlspecialchars(display_upload_url($row)); ?>" target="_blank">View</a>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

</div>
</div>

<script>
document.querySelectorAll('.file-upload-input').forEach(function (input) {
    input.addEventListener('change', function () {
        var fileName = this.files && this.files.length ? this.files[0].name : '';
        var label = this.parentElement.querySelector('.file-upload-name');
        if (label) {
            label.textContent = fileName || label.getAttribute('data-default-text');
        }
    });
});

var timetableTypeSelect = document.getElementById('timetable-type');
var timetableAssignSelect = document.getElementById('timetable-assign-to');

if (timetableTypeSelect && timetableAssignSelect) {
    var updateTimetableAssignees = function () {
        var selectedType = timetableTypeSelect.value;
        Array.prototype.forEach.call(timetableAssignSelect.options, function (option, index) {
            if (index === 0) {
                option.hidden = false;
                return;
            }

            var optionRole = option.getAttribute('data-role') || '';
            var isMatch = selectedType === '' || optionRole === selectedType;
            option.hidden = !isMatch;

            if (!isMatch && option.selected) {
                timetableAssignSelect.value = '';
            }
        });
    };

    timetableTypeSelect.addEventListener('change', updateTimetableAssignees);
    updateTimetableAssignees();
}
</script>

</body>
</html>
