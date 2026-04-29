<?php
$formData = [
    'name' => htmlspecialchars($_GET['name'] ?? ''),
    'email' => htmlspecialchars($_GET['email'] ?? ''),
    'role' => strtolower(trim($_GET['role'] ?? 'student')),
];
$email_error = $_GET['email_error'] ?? '';
$form_error = $_GET['error'] ?? '';
$success = ($_GET['success'] ?? '') === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BVAssist | Sign Up</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="signup-body">
<div class="signup-wrapper">
    <div class="signup-shell">
        <div class="signup-brand-panel" aria-hidden="true">
            <span class="section-badge">BV Assist</span>
            <h1>BV Assist</h1>
            <p class="brand-tagline">Manage academic resources easily</p>
            <div class="brand-card">
                <div class="brand-card-icon">📚</div>
                <div>
                    <strong>Academic portal</strong>
                    <p>Access notices, handouts, results, and timetable updates in one place.</p>
                </div>
            </div>
            <ul class="brand-features">
                <li><span class="brand-feature-icon">📝</span><span>Notices</span></li>
                <li><span class="brand-feature-icon">📄</span><span>Handouts</span></li>
                <li><span class="brand-feature-icon">🏆</span><span>Results</span></li>
            </ul>
        </div>

        <div class="signup-card">
            <div class="signup-header">
                <h2>Create Account</h2>
                <p class="subtitle">Join BV Assist portal</p>
            </div>

            <?php if (!empty($form_error)) { ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($form_error); ?></div>
            <?php } ?>

            <form id="signupForm" method="POST" action="../backend/signup.php">
                <div class="signup-field">
                    <label for="name">Full Name</label>
                    <div class="input-group signup-input-group">
                        <span class="field-icon" aria-hidden="true">👤</span>
                        <input type="text" name="name" id="name" value="<?php echo $formData['name']; ?>" required placeholder="Enter your full name">
                    </div>
                </div>

                <div class="signup-field">
                    <label for="email">Email</label>
                    <div class="input-group signup-input-group">
                        <span class="field-icon" aria-hidden="true">✉️</span>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="<?php echo $formData['email']; ?>"
                            pattern=".+@banasthali\.in"
                            required
                            placeholder="Enter your university email"
                        >
                    </div>
                    <?php if (!empty($email_error)) { ?>
                        <p class="error"><?php echo htmlspecialchars($email_error); ?></p>
                    <?php } ?>
                </div>

                <div class="signup-field">
                    <label for="password">Password</label>
                    <div class="input-group signup-input-group password-field">
                        <span class="field-icon" aria-hidden="true">🔒</span>
                        <input type="password" name="password" id="password" required placeholder="Create a password">
                        <span class="password-toggle" onclick="togglePassword()" aria-label="Toggle password">👁️</span>
                    </div>
                </div>

                <div class="signup-field">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-group signup-input-group password-field">
                        <span class="field-icon" aria-hidden="true">🔒</span>
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Re-enter your password">
                        <span class="password-toggle" onclick="toggleConfirmPassword()" aria-label="Toggle confirm password">👁️</span>
                    </div>
                </div>

                <div class="signup-field role-select">
                    <label>Select Role</label>
                    <div class="roles">
                        <div class="role <?php echo $formData['role'] === 'student' ? 'active' : ''; ?>" role="button" tabindex="0" aria-pressed="<?php echo $formData['role'] === 'student' ? 'true' : 'false'; ?>" data-role="Student" onclick="selectRole(this,'Student')" onkeydown="handleRoleKey(event, this, 'Student')">Student</div>
                        <div class="role <?php echo $formData['role'] === 'faculty' ? 'active' : ''; ?>" role="button" tabindex="0" aria-pressed="<?php echo $formData['role'] === 'faculty' ? 'true' : 'false'; ?>" data-role="Faculty" onclick="selectRole(this,'Faculty')" onkeydown="handleRoleKey(event, this, 'Faculty')">Faculty</div>
                        <div class="role <?php echo $formData['role'] === 'admin' ? 'active' : ''; ?>" role="button" tabindex="0" aria-pressed="<?php echo $formData['role'] === 'admin' ? 'true' : 'false'; ?>" data-role="Admin" onclick="selectRole(this,'Admin')" onkeydown="handleRoleKey(event, this, 'Admin')">Admin</div>
                    </div>
                    <input type="hidden" name="role" id="role" value="<?php echo htmlspecialchars($formData['role']); ?>" required>
                </div>

                <button type="submit" class="signup-submit-btn">Create Account</button>
                <button type="button" class="home-btn signup-home-btn" onclick="goHome()">Go to Home</button>

                <p class="signup-footer-text">
                    Already have an account? <a href="login.php">Log in</a>
                </p>
            </form>
        </div>
    </div>
</div>

<div id="popup" class="popup hidden">
    <p>Signup Successful 🎉</p>
    <button onclick="goHome()">Go to Home</button>
</div>

<script>
function togglePassword(){
    const p = document.getElementById("password");
    p.type = p.type === "password" ? "text" : "password";
}

function toggleConfirmPassword(){
    const p = document.getElementById("confirm_password");
    p.type = p.type === "password" ? "text" : "password";
}

function selectRole(el, role){
    document.querySelectorAll('.role').forEach(r => r.classList.remove('active'));
    document.querySelectorAll('.role').forEach(r => r.setAttribute('aria-pressed', 'false'));
    el.classList.add('active');
    el.setAttribute('aria-pressed', 'true');
    document.getElementById("role").value = role;
}

function handleRoleKey(event, el, role) {
    if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        selectRole(el, role);
    }
}

document.getElementById("signupForm").addEventListener("submit", function(e){
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirm_password").value;
    const role = document.getElementById("role").value;

    if(!name){
        alert("Please enter your full name.");
        e.preventDefault();
        return;
    }

    if(!email.endsWith("@banasthali.in")){
        alert("Use your official university email!");
        e.preventDefault();
        return;
    }

    const strong = /^(?=.*[A-Z])(?=.*[0-9])(?=.*[\W]).{8,}$/;
    if(!strong.test(password)){
        alert("Password must have 8+ chars, uppercase, number & special char");
        e.preventDefault();
        return;
    }

    if(password !== confirmPassword){
        alert("Passwords do not match");
        e.preventDefault();
        return;
    }

    if(!role){
        alert("Please select a role.");
        e.preventDefault();
        return;
    }
});

window.onload = function(){
    const params = new URLSearchParams(window.location.search);
    if(params.get("success") === "1"){
        document.getElementById("popup").classList.remove("hidden");
    }
};

function goHome(){
    window.location.href = "../backend/index.php";
}
</script>

</body>
</html>
