<?php
require_once "auth.php";
require_once "db.php";
require_once "upload_utils.php";

bvassist_require_role(['admin', 'faculty']);

function bvassist_handout_redirect_target(): string
{
    return bvassist_current_role() === 'faculty' ? 'faculty_dashboard.php' : 'admin_dashboard.php';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? '');

    if ($title === '') {
        bvassist_redirect_back(bvassist_handout_redirect_target(), 'error', 'Handout title is required.');
    }

    if (!isset($_FILES["file"])) {
        bvassist_redirect_back(bvassist_handout_redirect_target(), 'error', 'Please choose a file to upload.');
    }

    $upload = bvassist_store_upload($_FILES["file"]);
    if (!$upload['ok']) {
        bvassist_redirect_back(bvassist_handout_redirect_target(), 'error', $upload['error']);
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO handouts (title, file_path, uploaded_by) VALUES (?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssi",
        $title,
        $upload['file_path'],
        bvassist_current_user_id()
    );

    if (mysqli_stmt_execute($stmt)) {
        if (bvassist_current_role() === 'faculty') {
            header("Location: faculty_dashboard.php?success=1");
            exit();
        }

        bvassist_redirect_back('admin_dashboard.php', 'success', 'Handout uploaded successfully.');
    }

    bvassist_redirect_back(bvassist_handout_redirect_target(), 'error', 'Database insert failed.');
}

$uploadStatus = $_GET['upload_status'] ?? '';
$uploadMessage = $_GET['upload_message'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Handout</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body class="dashboard-body faculty-dashboard-body">
<div class="dashboard-wrapper">
    <?php include "sidebar.php"; ?>

    <div class="main-content">
        <div class="top-header faculty-hero fade-in">
            <div>
                <span class="section-badge">Faculty Upload</span>
                <h1>Upload Handout</h1>
                <p class="welcome-text">Add lecture notes, study material, and supporting resources from one focused page.</p>
            </div>
        </div>

        <?php if ($uploadMessage !== '') { ?>
            <div class="feedback-alert <?php echo $uploadStatus === 'success' ? 'feedback-success' : 'feedback-error'; ?> fade-in">
                <?php echo htmlspecialchars($uploadMessage); ?>
            </div>
        <?php } ?>

        <div class="dash-card upload-card fade-in" style="max-width: 720px;">
            <div class="card-copy">
                <h3>Handout Details</h3>
                <p>Choose a clear title and upload a PDF or image file.</p>
            </div>

            <form action="upload_handout.php" method="POST" enctype="multipart/form-data">
                <div class="form-field">
                    <label for="handout-title-page">Title</label>
                    <input id="handout-title-page" type="text" name="title" placeholder="Enter handout title" required>
                </div>

                <div class="form-field">
                    <label for="handout-file-page">File</label>
                    <input id="handout-file-page" type="file" name="file" accept="application/pdf,image/*" required>
                </div>

                <button type="submit" class="primary-btn">Upload</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
