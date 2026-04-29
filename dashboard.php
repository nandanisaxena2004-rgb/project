<?php
require_once "auth.php";

bvassist_start_session();

// 🔒 Check login
if (!isset($_SESSION['email'])) {
    bvassist_login_redirect();
}

// 🎯 Get role
$role = strtolower(trim($_SESSION['role'] ?? ''));

// 🔀 Redirect based on role
if ($role === 'admin') {

    bvassist_redirect_for_role('admin');

} 
elseif ($role === 'faculty') {

    bvassist_redirect_for_role('faculty');

} 
elseif ($role === 'student') {

    bvassist_redirect_for_role('student');

} 
else {

    // ❌ Invalid role → logout
    session_destroy();
    bvassist_login_redirect();

}

?>
