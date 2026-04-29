<?php
require_once "auth.php";
require_once "db.php";
require_once "upload_utils.php";
require_once "functions.php";

bvassist_require_role(['admin', 'student']);

$results = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM results ORDER BY created_at DESC");
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $results = bvassist_fetch_stmt_rows($stmt);
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Results</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>

<body class="dashboard-body">
<div class="dashboard-wrapper">

<?php include "sidebar.php"; ?>

<div class="main-content">
    <h2>Published Results</h2>

    <?php if (!empty($results)) { ?>
        <?php foreach ($results as $row) { ?>
            <div class="file-card">
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <a href="<?php echo htmlspecialchars(bvassist_upload_url_from_path($row['file_path'] ?? ('uploads/' . ($row['file_name'] ?? '')))); ?>" target="_blank">View</a>
                <a href="<?php echo htmlspecialchars(bvassist_upload_url_from_path($row['file_path'] ?? ('uploads/' . ($row['file_name'] ?? '')))); ?>" download>Download</a>
            </div>
        <?php } ?>
    <?php } else { ?>
        <p>No results found.</p>
    <?php } ?>
</div>

</div>
</body>
</html>
