<?php
require_once "auth.php";
require_once "db.php";

bvassist_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php#feedback-section");
    exit();
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin_dashboard.php#feedback-section");
    exit();
}

$stmt = mysqli_prepare($conn, "DELETE FROM feedback WHERE id=?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
}

header("Location: admin_dashboard.php?feedback_status=success&feedback_message=" . urlencode('Feedback deleted.') . "#feedback-section");
exit();
