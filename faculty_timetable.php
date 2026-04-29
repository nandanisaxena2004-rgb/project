<?php
require_once "auth.php";
include "db.php";
require_once "upload_utils.php";
require_once "openai_timetable_parser.php";

bvassist_require_role(['faculty']);
$userId = bvassist_current_user_id();

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM timetables WHERE user_id=? ORDER BY created_at DESC"
);

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$timetables = [];

while ($result && ($row = mysqli_fetch_assoc($result))) {
    $timetables[] = $row;
}

mysqli_stmt_close($stmt);

$entriesByTimetable = bvassist_fetch_timetable_entries_by_timetable_ids(
    $conn,
    array_column($timetables, 'id')
);
?>

<!DOCTYPE html>
<html>
<head>
<title>Your Teaching Schedule</title>
<link rel="stylesheet" href="../frontend/css/style.css">
</head>

<body class="dashboard-body">
<div class="dashboard-wrapper">

<?php include "sidebar.php"; ?>

<div class="main-content">
<h2>📅 Your Teaching Schedule</h2>

<?php if (empty($timetables)) { ?>
    <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">📭</div>
        <p class="empty-state-message">No data available</p>
    </div>
<?php } ?>

<?php foreach ($timetables as $row) { ?>
    <div class="file-card">
        <h3><?php echo htmlspecialchars($row["title"]); ?></h3>

        <div class="table-actions">
            <a class="table-action-btn" href="<?php echo htmlspecialchars(bvassist_upload_url_from_path($row["file_path"] ?? ('uploads/' . ($row["file_name"] ?? '')))); ?>" target="_blank" title="View timetable source">
                <span aria-hidden="true">👁</span>
                <span>View</span>
            </a>

            <a class="table-action-btn" href="<?php echo htmlspecialchars(bvassist_upload_url_from_path($row["file_path"] ?? ('uploads/' . ($row["file_name"] ?? '')))); ?>" download title="Download timetable source">
                <span aria-hidden="true">⬇</span>
                <span>Download</span>
            </a>
        </div>

        <?php $entries = $entriesByTimetable[(int) ($row['id'] ?? 0)] ?? []; ?>
        <?php if (!empty($entries)) { ?>
            <div class="table-shell" style="margin-top:16px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($entries as $entry) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry["day"]); ?></td>
                            <td><?php echo htmlspecialchars($entry["time_slot"]); ?></td>
                            <td><?php echo htmlspecialchars($entry["subject"]); ?></td>
                            <td><?php echo htmlspecialchars($entry["room"] !== '' ? $entry["room"] : '-'); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="empty-state empty-state-inline">
                <div class="empty-state-icon" aria-hidden="true">📂</div>
                <p class="empty-state-message">No data available</p>
            </div>
        <?php } ?>
    </div>
<?php } ?>

</div>
</div>
</body>
</html>
