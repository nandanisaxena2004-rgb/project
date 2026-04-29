<?php
require_once "auth.php";
require_once "db.php";
require_once "functions.php";

bvassist_require_role(['student', 'faculty']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . bvassist_dashboard_for_role(bvassist_current_role()) . "#feedback-section");
    exit();
}

function feedback_redirect(string $status, string $message): void
{
    $target = bvassist_dashboard_for_role(bvassist_current_role());
    $query = http_build_query([
        'feedback_status' => $status,
        'feedback_message' => $message,
    ]);

    header('Location: ' . $target . '?' . $query . '#feedback-section');
    exit();
}

$message = trim($_POST['message'] ?? '');
if ($message === '') {
    feedback_redirect('error', 'Feedback message cannot be empty.');
}

$email = trim($_SESSION['email'] ?? '');
$role = bvassist_current_role();

if (
    bvassist_has_column($conn, 'feedback', 'email') &&
    bvassist_has_column($conn, 'feedback', 'role') &&
    bvassist_has_column($conn, 'feedback', 'status')
) {
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO feedback (email, role, message, status) VALUES (?, ?, ?, 'pending')"
    );
} elseif (bvassist_has_column($conn, 'feedback', 'student_email')) {
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO feedback (student_email, message) VALUES (?, ?)"
    );
} else {
    feedback_redirect('error', 'Feedback table is not configured yet.');
}

if (!$stmt) {
    feedback_redirect('error', 'Could not prepare feedback submission.');
}

if (
    bvassist_has_column($conn, 'feedback', 'email') &&
    bvassist_has_column($conn, 'feedback', 'role') &&
    bvassist_has_column($conn, 'feedback', 'status')
) {
    mysqli_stmt_bind_param($stmt, "sss", $email, $role, $message);
} else {
    mysqli_stmt_bind_param($stmt, "ss", $email, $message);
}

if (mysqli_stmt_execute($stmt)) {
    feedback_redirect('success', 'Feedback submitted successfully.');
}

feedback_redirect('error', 'Feedback submission failed.');
