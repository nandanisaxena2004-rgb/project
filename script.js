// =====================
// AUTH PROTECTION
// =====================

document.addEventListener("DOMContentLoaded", function () {

    const currentPage = window.location.pathname.split("/").pop();
    const loggedIn = localStorage.getItem("isLoggedIn");
    const user = localStorage.getItem("currentUser");

    if (currentPage === "dashboard.html") {

        if (loggedIn !== "true" || !user) {
            window.location.href = "login.html";
        }

    }

});
// =====================
// CLEAR SESSION ON INDEX PAGE
// =====================

const currentPage = window.location.pathname.split("/").pop();

if (currentPage === "index.html") {
    sessionStorage.removeItem("isLoggedIn");
    sessionStorage.removeItem("currentUser");
}


// =====================
// GENERAL FUNCTIONS
// =====================

function goTo(page) {
    window.location.href = page;
}

function logout() {
    localStorage.removeItem("isLoggedIn");
    localStorage.removeItem("currentUser");
    window.location.href = "login.html";
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

function openStudentPortal() {

    const loggedIn = localStorage.getItem("isLoggedIn");

    if (loggedIn === "true") {
        window.location.href = "dashboard.html";
    } else {
        window.location.href = "login.html";
    }

}


// =====================
// DOM READY
// =====================

document.addEventListener("DOMContentLoaded", function () {

    // =====================
// LOGIN
// =====================

const loginForm = document.getElementById("login-form");

if (loginForm) {

    loginForm.addEventListener("submit", function (e) {

        e.preventDefault();

        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;
        const error = document.getElementById("login-error");

        error.textContent = "";

        // Email validation
        if (!email.endsWith("@banasthali.in")) {
            error.textContent =
                "Please login using your official @banasthali.in email ID.";
            return;
        }

        // Password validation (demo password)
        const correctPassword = "bvassist123";

        if (password !== correctPassword) {
            error.textContent = "Incorrect password.";
            return;
        }

        // Login success
        alert("Login successful");

        sessionStorage.setItem("isLoggedIn", "true");
        sessionStorage.setItem("currentUser", email);

        window.location.href = "dashboard.html";

    });

}
    // =====================
// PREVENT BACK NAVIGATION AFTER LOGOUT
// =====================

window.history.pushState(null, null, window.location.href);

window.onpopstate = function () {
    window.history.pushState(null, null, window.location.href);
};
const page = window.location.pathname.split("/").pop();
const loggedIn = sessionStorage.getItem("isLoggedIn");

if (page === "dashboard.html" && loggedIn !== "true") {
    window.location.href = "login.html";
}
if (page === "login.html" && loggedIn === "true") {
    window.location.href = "dashboard.html";
}
function logout() {
    sessionStorage.removeItem("isLoggedIn");
    sessionStorage.removeItem("currentUser");
    window.location.replace("login.html");
}

    // =====================
    // SIGNUP
    // =====================

    const signupForm = document.getElementById("signup-form");

    if (signupForm) {

        signupForm.addEventListener("submit", function (e) {

            e.preventDefault();

            const email = document.getElementById("signup-email").value.trim();
            const role = document.getElementById("signup-role").value;
            const error = document.getElementById("signup-error");

            error.style.color = "red";
            error.textContent = "";

            if (role === "Student" && !email.endsWith("@banasthali.in")) {

                error.textContent =
                    "Students must use official @banasthali.in email ID.";

                return;
            }

            alert("Signup successful");
            signupForm.reset();

        });

    }


    // =====================
    // FORGOT PASSWORD
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


    // =====================
    // STUDENT COURSE DISPLAY
    // =====================

    const email = sessionStorage.getItem("currentUser");

    if (!email) return;

    let course = "B.Tech CSE (AI) - Semester 5";

    const courseElement = document.getElementById("student-course");

    if (courseElement) {
        courseElement.textContent = course;
    }

});
// =====================
// SESSION TIMEOUT (15 minutes)
// =====================

let sessionTimer;

function startSessionTimer() {

    clearTimeout(sessionTimer);

    sessionTimer = setTimeout(function () {

        alert("Session expired due to inactivity. Please login again.");

        sessionStorage.removeItem("isLoggedIn");
        sessionStorage.removeItem("currentUser");

        window.location.replace("login.html");

    }, 15 * 60 * 1000); // 15 minutes

}

// Reset timer on user activity
["click","mousemove","keypress","scroll"].forEach(function(event){

    document.addEventListener(event, startSessionTimer);

});

// Start timer when page loads
document.addEventListener("DOMContentLoaded", startSessionTimer);


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