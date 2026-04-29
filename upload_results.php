<?php
require_once "auth.php";
require_once "db.php";
require_once "upload_utils.php";

bvassist_require_role(['admin']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? '');

    if ($title === '') {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'Result title is required.');
    }

    if (!isset($_FILES["file"])) {
        bvassist_redirect_back('admin_dashboard.php', 'error', 'Please choose a file to upload.');
    }

    $upload = bvassist_store_upload($_FILES["file"]);
    if (!$upload['ok']) {
        bvassist_redirect_back('admin_dashboard.php', 'error', $upload['error']);
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO results (title, file_path, uploaded_by) VALUES (?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssi",
        $title,
        $upload['file_path'],
        bvassist_current_user_id()
    );

    if (mysqli_stmt_execute($stmt)) {
        bvassist_redirect_back('admin_dashboard.php', 'success', 'Result uploaded successfully.');
    }

    bvassist_redirect_back('admin_dashboard.php', 'error', 'Database insert failed.');
}

bvassist_redirect_back('admin_dashboard.php', 'error', 'Invalid request.');
