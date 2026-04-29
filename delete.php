<?php
require_once "auth.php";
require_once "db.php";
require_once "upload_utils.php";

bvassist_require_role(['admin']);

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: admin_dashboard.php?upload_status=error&upload_message=" . urlencode("Something went wrong.") . "#handouts-section");
    exit();
}

// get file path
$stmt = mysqli_prepare($conn, "SELECT file_path, file_name FROM handouts WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;

if ($row) {
    $relativePath = trim((string)($row["file_path"] ?? ''));
    if ($relativePath === '' && !empty($row["file_name"])) {
        $relativePath = 'uploads/' . $row["file_name"];
    }

    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['../', '/', '\\'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ltrim($relativePath, '/'));

    if (file_exists($file)) {
        unlink($file);
    }
}

// delete from db
$stmt = mysqli_prepare($conn, "DELETE FROM handouts WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

header("Location: admin_dashboard.php");
exit();
?>
