<?php

session_start();

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email and password required';
    header("Location: ../index.php");
    exit();
}

$query = "SELECT user_id, email, password, role FROM users WHERE email = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Email not found';
    header("Location: ../index.php");
    exit();
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    $_SESSION['error'] = 'Invalid password';
    header("Location: ../index.php");
    exit();
}

// ✅ Session set
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

// ✅ Role-based redirect
if ($user['role'] == 'Admin') {
    header("Location: ../admin/dashboard.php");
} 
elseif ($user['role'] == 'Faculty') {
    header("Location: ../faculty/dashboard.php");
} 
else {
    header("Location: ../student/dashboard.php");
}

exit();

?>