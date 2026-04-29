<?php
require_once "auth.php";
require_once "db.php";
require_once "functions.php";

bvassist_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php#feedback-section");
    exit();
}

$statusColumnExists = bvassist_has_column($conn, 'feedback', 'status');
if (!$statusColumnExists) {
    header("Location: admin_dashboard.php?feedback_status=error&feedback_message=" . urlencode('Feedback status column is unavailable. Update the database schema.') . "#feedback-section");
    exit();
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin_dashboard.php?feedback_status=error&feedback_message=" . urlencode('Invalid feedback item.') . "#feedback-section");
    exit();
}

$stmt = mysqli_prepare($conn, "UPDATE feedback SET status='resolved' WHERE id=?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
}

header("Location: admin_dashboard.php?feedback_status=success&feedback_message=" . urlencode('Feedback marked as resolved.') . "#feedback-section");
exit();
