<?php
$email = htmlspecialchars($_GET['email'] ?? '');
$email_error = $_GET['email_error'] ?? '';
$login_error = $_GET['login_error'] ?? '';
$reset_success = $_GET['reset_success'] ?? '';
$selectedRole = strtolower(trim($_GET['role'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BVAssist | Login</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="login-body">
<div class="login-wrapper">
    <div class="login-shell">
        <section class="login-visual" aria-hidden="true">
            <div class="login-visual-badge">BV Assist</div>
            <h1>BV Assist</h1>
            <p class="login-visual-tagline">Manage academic resources easily</p>
            <div class="visual-card">
                <div class="visual-card-icon">📚</div>
                <div>
                    <strong>Centralized access</strong>
                    <span>Keep handouts, timetables, notices, and results in one place.</span>
                </div>
            </div>
            <div class="visual-pulse visual-pulse-one"></div>
            <div class="visual-pulse visual-pulse-two"></div>
            <div class="visual-grid"></div>
        </section>

        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p class="subtitle">Sign in to continue to your dashboard.</p>
            </div>

            <form method="POST" action="../backend/login.php" id="login-form">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    pattern=".+@banasthali\.in"
                    value="<?php echo $email; ?>"
                    required
                >
                <?php if (!empty($email_error) || !empty($login_error) || !empty($reset_success)) { ?>
                    <p class="error" style="color: <?php echo !empty($reset_success) ? '#2e8b57' : '#c0392b'; ?>;">
                        <?php echo htmlspecialchars($reset_success ?: ($email_error ?: $login_error)); ?>
                    </p>
                <?php } ?>

                <label>Password</label>
                <div class="password-field">
                    <input type="password" name="password" id="password" placeholder="Enter password" required>
                    <span onclick="togglePassword()">👁️</span>
                </div>

                <div class="login-options">
                    <label><input type="checkbox"> Remember me</label>
                    <a href="forgot_password.php">Forgot password?</a>
                </div>

                <input type="hidden" name="role" id="role" value="<?php echo htmlspecialchars($selectedRole); ?>">

                <button type="submit" class="login-submit-btn" id="login-submit-btn">
                    <span class="btn-label">Log In</span>
                    <span class="btn-spinner" aria-hidden="true"></span>
                </button>
            </form>

            <div class="switch">
                <p>Don’t have an account? <a href="signup.php">Sign Up</a></p>
                <button class="home-btn" type="button" onclick="goHome()">Go to Home</button>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(){
    const p = document.getElementById("password");
    p.type = p.type === "password" ? "text" : "password";
}

const params = new URLSearchParams(window.location.search);
const role = params.get("role");
if(role){
    document.getElementById("role").value = role.toLowerCase();
}

history.pushState(null, null, location.href);
window.onpopstate = function () {
    history.go(1);
};

function goHome() {
    window.location.href = "../backend/index.php";
}

const loginForm = document.getElementById("login-form");
const loginSubmitBtn = document.getElementById("login-submit-btn");
const loginBtnLabel = loginSubmitBtn.querySelector(".btn-label");

loginForm.addEventListener("submit", function () {
    loginSubmitBtn.disabled = true;
    loginSubmitBtn.classList.add("is-loading");
    loginBtnLabel.textContent = "Signing in...";
});
</script>
</body>
</html>
