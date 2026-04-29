<?php
require_once "auth.php";
require_once "db.php";
require_once "upload_utils.php";
require_once "openai_timetable_parser.php";

bvassist_require_role(['admin']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? '');
    $type = strtolower(trim($_POST["type"] ?? ''));
    $assignedUserId = (int) ($_POST["assign_to"] ?? 0);
    $adminUserId = bvassist_current_user_id();

    if ($title === '') {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'Timetable title is required.');
    }

    if (!in_array($type, ['student', 'faculty'], true)) {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'Please choose a timetable type.');
    }

    if ($assignedUserId <= 0) {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'Please choose the student or faculty member who should own this timetable.');
    }

    if (!isset($_FILES["file"])) {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'Please choose a file to upload.');
    }

    $userStmt = mysqli_prepare($conn, "SELECT id, LOWER(role) AS role FROM users WHERE id = ? LIMIT 1");
    if (!$userStmt) {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'Could not validate the selected timetable owner.');
    }

    mysqli_stmt_bind_param($userStmt, "i", $assignedUserId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $assignedUser = $userResult ? mysqli_fetch_assoc($userResult) : null;
    mysqli_stmt_close($userStmt);

    if (!$assignedUser || !in_array($assignedUser['role'] ?? '', ['student', 'faculty'], true)) {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'The selected timetable owner is invalid.');
    }

    if (($assignedUser['role'] ?? '') !== $type) {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'The selected user role does not match the timetable type.');
    }

    $upload = bvassist_store_upload($_FILES["file"]);
    if (!$upload['ok']) {
        bvassist_redirect_back('admin_dashboard.php', 'error', $upload['error']);
    }

    if (!bvassist_ensure_timetables_assignment_schema($conn)) {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'Could not prepare the timetable assignment schema.');
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO timetables (title, type, user_id, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'Could not prepare timetable upload.');
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssisi",
        $title,
        $type,
        $assignedUserId,
        $upload['file_path'],
        $adminUserId
    );

    if (mysqli_stmt_execute($stmt)) {
        $timetableId = (int) mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $absoluteFilePath = bvassist_upload_dir() . DIRECTORY_SEPARATOR . ($upload['file_name'] ?? basename((string) $upload['file_path']));
        $aiResult = bvassist_extract_timetable_with_ai(
            $conn,
            $timetableId,
            $title,
            $type,
            $absoluteFilePath
        );

        $message = 'Timetable uploaded successfully.';
        if ($aiResult['ok']) {
            $message .= ' AI extracted ' . (int) $aiResult['count'] . ' timetable row(s).';
            if (!empty($aiResult['warning'])) {
                $message .= ' ' . $aiResult['warning'];
            }
        } else {
            $message .= ' File upload worked, but AI extraction was skipped: ' . ($aiResult['error'] ?? 'Unknown parser error.') . '.';
        }

        bvassist_redirect_back('admin_dashboard.php', 'success', $message);
    }

    mysqli_stmt_close($stmt);
    bvassist_redirect_back('admin_dashboard.php', 'error', 'Database insert failed.');
}

bvassist_redirect_back('admin_dashboard.php', 'error', 'Invalid request.');
