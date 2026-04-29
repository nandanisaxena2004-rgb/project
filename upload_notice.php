<?php
require_once "auth.php";
require_once "db.php";
require_once "upload_utils.php";

bvassist_require_role(['admin', 'faculty']);

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? '');
    $content = trim($_POST["message"] ?? '');
    $role = strtolower(trim($_SESSION["role"]));
    $createdBy = (string) bvassist_current_user_id();

    if ($title === '') {
        $message = 'Title is required.';
        $messageType = 'error';
    } elseif (!isset($_FILES["file"])) {
        $message = 'Please choose a file to upload.';
        $messageType = 'error';
    } else {
        $upload = bvassist_store_upload($_FILES["file"]);

        if (!$upload['ok']) {
            $message = $upload['error'];
            $messageType = 'error';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO notices (title, content, file_path, created_by, role) VALUES (?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssss",
                $title,
                $content,
                $upload['file_path'],
                $createdBy,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {
                bvassist_redirect_back('admin_dashboard.php', 'success', 'Notice uploaded successfully.');
            }

            $message = 'Database insert failed.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Upload Notice</title>
<link rel="stylesheet" href="../frontend/css/style.css">

<style>
body {
    background: #f5f7fb;
    font-family: 'Segoe UI', sans-serif;
}

.page-container {
    padding: 40px 20px;
}

.form-card {
    max-width: 600px;
    margin: 40px auto;
    padding: 30px;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

h1 {
    margin-bottom: 5px;
}

p {
    color: #666;
    margin-bottom: 20px;
}

label {
    font-weight: 500;
}

input, textarea {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #ddd;
    margin-bottom: 15px;
    font-size: 14px;
}

textarea {
    min-height: 120px;
    resize: none;
}

input[type="file"] {
    padding: 10px;
}

.btn {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    background: linear-gradient(90deg, #4facfe, #43e97b);
    color: white;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

.btn:hover {
    opacity: 0.9;
}

.message {
    margin-bottom: 15px;
    font-weight: 500;
}
</style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="main-content">

<div class="page-container">
<div class="form-card">

<h1>📢 Upload Notice</h1>
<p>Add important announcements for students and faculty.</p>

<?php if ($message !== '') { ?>
    <p class="message" style="color:<?php echo $messageType === 'success' ? 'green' : 'red'; ?>;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php } elseif (!empty($_GET['upload_message'])) { ?>
    <p class="message" style="color:<?php echo ($_GET['upload_status'] ?? '') === 'success' ? 'green' : 'red'; ?>;">
        <?php echo htmlspecialchars($_GET['upload_message']); ?>
    </p>
<?php } ?>

<form method="POST" enctype="multipart/form-data">

    <label>Title</label>
    <input type="text" name="title" placeholder="Enter notice title" required>

    <label>Message</label>
    <textarea name="message" placeholder="Enter notice content"></textarea>

    <label>Upload File</label>
    <input type="file" name="file" accept="application/pdf,image/*" required>

    <button type="submit" class="btn">Upload Notice</button>

</form>

</div>
</div>

</div>

</body>
</html>