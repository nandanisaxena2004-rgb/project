<?php
$email = htmlspecialchars($_GET['email'] ?? '');
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BVAssist | Reset Password</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="login-body">
<div class="login-wrapper">
    <div class="login-shell">
        <section class="login-visual" aria-hidden="true">
            <div class="login-visual-badge">Password Reset</div>
            <h1>Reset Password</h1>
            <p class="login-visual-tagline">Set a new password using your official Banasthali email.</p>
            <div class="visual-card">
                <div class="visual-card-icon">🔐</div>
                <div>
                    <strong>Quick recovery</strong>
                    <span>Update your password securely and return to your dashboard.</span>
                </div>
            </div>
            <div class="visual-pulse visual-pulse-one"></div>
            <div class="visual-pulse visual-pulse-two"></div>
            <div class="visual-grid"></div>
        </section>

        <div class="login-card">
            <div class="login-header">
                <h2>Forgot Password?</h2>
                <p class="subtitle">Enter your email and choose a new password.</p>
            </div>

            <form method="POST" action="../backend/forgot_password.php" id="forgot-password-form">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    placeholder="Enter your @banasthali.in email"
                    pattern=".+@banasthali\.in"
                    value="<?php echo $email; ?>"
                    required
                >

                <label>New Password</label>
                <div class="password-field">
                    <input type="password" name="password" id="password" placeholder="Minimum 6 characters" minlength="6" required>
                    <span onclick="togglePassword('password')">👁️</span>
                </div>

                <label>Confirm Password</label>
                <div class="password-field">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter new password" minlength="6" required>
                    <span onclick="togglePassword('confirm_password')">👁️</span>
                </div>

                <?php if ($error !== '' || $success !== '') { ?>
                    <p class="error" style="color: <?php echo $success !== '' ? '#2e8b57' : '#c0392b'; ?>;">
                        <?php echo htmlspecialchars($success !== '' ? $success : $error); ?>
                    </p>
                <?php } ?>

                <button type="submit" class="login-submit-btn" id="forgot-password-submit-btn">
                    <span class="btn-label">Update Password</span>
                    <span class="btn-spinner" aria-hidden="true"></span>
                </button>
            </form>

            <div class="switch">
                <p>Remember your password? <a href="login.php">Back to Login</a></p>
                <button class="home-btn" type="button" onclick="goHome()">Go to Home</button>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.type = input.type === "password" ? "text" : "password";
    }
}

function goHome() {
    window.location.href = "../backend/index.php";
}

const forgotPasswordForm = document.getElementById("forgot-password-form");
const forgotPasswordSubmitBtn = document.getElementById("forgot-password-submit-btn");
const forgotPasswordBtnLabel = forgotPasswordSubmitBtn.querySelector(".btn-label");

forgotPasswordForm.addEventListener("submit", function (event) {
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirm_password").value;

    if (password.length < 6) {
        event.preventDefault();
        alert("Password must be at least 6 characters.");
        return;
    }

    if (password !== confirmPassword) {
        event.preventDefault();
        alert("Passwords do not match.");
        return;
    }

    forgotPasswordSubmitBtn.disabled = true;
    forgotPasswordSubmitBtn.classList.add("is-loading");
    forgotPasswordBtnLabel.textContent = "Updating...";
});
</script>
</body>
</html>
