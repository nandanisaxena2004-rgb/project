<?php
require_once "auth.php";
require_once "db.php";
require_once "upload_utils.php";
require_once "functions.php";

bvassist_require_role(['admin', 'faculty', 'student']);

$adminNoticeStmt = mysqli_prepare($conn, "SELECT * FROM notices WHERE LOWER(role)=? ORDER BY created_at DESC");
$facultyNoticeStmt = mysqli_prepare($conn, "SELECT * FROM notices WHERE LOWER(role)=? ORDER BY created_at DESC");
$adminNotices = [];
$facultyNotices = [];

if ($adminNoticeStmt) {
    $adminRole = 'admin';
    mysqli_stmt_bind_param($adminNoticeStmt, "s", $adminRole);
    mysqli_stmt_execute($adminNoticeStmt);
    $adminNotices = bvassist_fetch_stmt_rows($adminNoticeStmt);
    mysqli_stmt_close($adminNoticeStmt);
}

if ($facultyNoticeStmt) {
    $facultyRole = 'faculty';
    mysqli_stmt_bind_param($facultyNoticeStmt, "s", $facultyRole);
    mysqli_stmt_execute($facultyNoticeStmt);
    $facultyNotices = bvassist_fetch_stmt_rows($facultyNoticeStmt);
    mysqli_stmt_close($facultyNoticeStmt);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Notifications</title>
<link rel="stylesheet" href="../frontend/css/style.css">
</head>

<body class="dashboard-body">

<div class="dashboard-wrapper">

<?php include "sidebar.php"; ?>

<div class="main-content">

<h1>🔔 Notifications</h1>

<!-- ADMIN -->
<h2>Admin Notifications</h2>

<?php if (empty($adminNotices)) { ?>
    <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">📭</div>
        <p class="empty-state-message">No data available</p>
    </div>
<?php } ?>

<?php foreach ($adminNotices as $row) { ?>
    <div class="file-card">
        <h3><?php echo htmlspecialchars($row["title"]); ?></h3>

        <a href="<?php echo htmlspecialchars(bvassist_upload_url_from_path($row["file_path"] ?? ('uploads/' . ($row["file_name"] ?? '')))); ?>" target="_blank">
            View
        </a>

        <a href="<?php echo htmlspecialchars(bvassist_upload_url_from_path($row["file_path"] ?? ('uploads/' . ($row["file_name"] ?? '')))); ?>" download>
            Download
        </a>
    </div>
<?php } ?>


<!-- FACULTY -->
<h2>Faculty Notifications</h2>

<?php if (empty($facultyNotices)) { ?>
    <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">📭</div>
        <p class="empty-state-message">No data available</p>
    </div>
<?php } ?>

<?php foreach ($facultyNotices as $row) { ?>
    <div class="file-card">
        <h3><?php echo htmlspecialchars($row["title"]); ?></h3>

        <a href="<?php echo htmlspecialchars(bvassist_upload_url_from_path($row["file_path"] ?? ('uploads/' . ($row["file_name"] ?? '')))); ?>" target="_blank">
            View
        </a>

        <a href="<?php echo htmlspecialchars(bvassist_upload_url_from_path($row["file_path"] ?? ('uploads/' . ($row["file_name"] ?? '')))); ?>" download>
            Download
        </a>
    </div>
<?php } ?>

</div>
</div>

</body>
</html>
