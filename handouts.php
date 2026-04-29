<?php
require_once "auth.php";
require_once "db.php";
require_once "upload_utils.php";
require_once "functions.php";

bvassist_require_role(['admin', 'faculty', 'student']);

$handouts = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM handouts ORDER BY created_at DESC");
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $handouts = bvassist_fetch_stmt_rows($stmt);
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Handouts</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>

<body class="dashboard-body">
<div class="dashboard-wrapper">

<?php include "sidebar.php"; ?>

<div class="main-content">
<h2>Available Handouts</h2>

<?php foreach ($handouts as $row) { ?>
    <div class="file-card">
        <h3><?php echo htmlspecialchars($row["title"]); ?></h3>

        <a href="<?php echo htmlspecialchars(bvassist_upload_url_from_path($row["file_path"] ?? ('uploads/' . ($row["file_name"] ?? '')))); ?>" download>
            Download
        </a>
    </div>
<?php } ?>

<?php if (empty($handouts)) { ?>
    <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">📂</div>
        <p class="empty-state-message">No data available</p>
    </div>
<?php } ?>

</div>
</div>
</body>
</html>
