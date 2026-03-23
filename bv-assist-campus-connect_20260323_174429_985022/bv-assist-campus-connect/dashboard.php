
<?php
session_start();

echo "ROLE: " . $_SESSION['role'];
exit();
?>

session_start();

// 🔒 Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 🎯 Get role
$role = $_SESSION['role'];

// 🔀 Redirect based on role
if ($role === 'Admin') {

    header("Location: admin-dashboard.php");
    exit();

} 
elseif ($role === 'Faculty') {

    header("Location: faculty-dashboard.php");
    exit();

} 
elseif ($role === 'Student') {

    header("Location: student-dashboard.php");
    exit();

} 
else {

    // ❌ Invalid role → logout
    session_destroy();
    header("Location: index.php");
    exit();

}

?>