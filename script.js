// =====================
// GENERAL FUNCTIONS
// =====================

function goTo(page) {
    window.location.href = page;
}

function logout() {
    window.location.href = "../backend/logout.php";
}

function showForgot() {
    document.getElementById("login-view").style.display = "none";
    document.getElementById("forgot-view").style.display = "block";
}

function showLogin() {
    document.getElementById("forgot-view").style.display = "none";
    document.getElementById("login-view").style.display = "block";
}

function togglePassword() {
    const passwordInput = document.getElementById("password");

    if (passwordInput) {
        passwordInput.type =
            passwordInput.type === "password" ? "text" : "password";
    }
}

// =====================
// DOM READY
// =====================

document.addEventListener("DOMContentLoaded", function () {

    // =====================
    // LOGIN (FIXED - NO JSON)
    // =====================

    const loginForm = document.getElementById("login-form");

    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {

            const email = document.getElementById("email").value.trim();
            const error = document.getElementById("login-error");

            error.textContent = "";

            if (!email.endsWith("@banasthali.in")) {
                e.preventDefault(); // stop submit
                error.textContent =
                    "Please login using your official @banasthali.in email ID.";
            }

            // If valid → form submits normally to PHP
        });
    }

    // =====================
    // FORGOT PASSWORD (UI ONLY)
    // =====================

    const forgotForm = document.getElementById("forgot-form");

    if (forgotForm) {
        forgotForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const email = document.getElementById("forgot-email").value.trim();
            const errorText = document.getElementById("forgot-error");

            errorText.textContent = "";

            if (!email) {
                errorText.textContent = "Please enter your registered email.";
                return;
            }

            errorText.style.color = "green";
            errorText.textContent =
                "Password reset link has been sent to your email.";
        });
    }

});

// =====================
// SCROLL REVEAL
// =====================

function revealOnScroll() {
    document.querySelectorAll(".reveal").forEach(el => {
        const top = el.getBoundingClientRect().top;
        if (top < window.innerHeight - 100) {
            el.classList.add("active");
        }
    });
}

window.addEventListener("scroll", revealOnScroll);
window.addEventListener("load", revealOnScroll);
