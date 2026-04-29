<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BV Assist</title>

    <link rel="stylesheet" href="../frontend/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="home-page">

<main class="home-hero">
    <div class="home-hero-inner">
        <section class="home-hero-copy">
            <div class="hero-brand-line">
                <img src="../assets/logo.png" alt="Banasthali Vidyapith logo" class="hero-brand-logo">
                <div class="hero-brand-copy">
                    <span class="section-badge">BV Assist</span>
                    <span class="hero-university-name">Banasthali Vidyapith</span>
                </div>
            </div>
            <h1>Welcome to BV Assist</h1>
            <p class="home-hero-subtitle">A smart academic portal for Banasthali Vidyapith</p>
            <p class="home-hero-description">
                Stay connected to your academic life with one clean dashboard for notices, results,
                handouts, and timetable updates.
            </p>

            <div class="home-hero-actions">
                <a href="../frontend/signup.php" class="home-hero-btn primary">Explore Portal</a>
                <a href="../frontend/login.php" class="home-hero-btn secondary">Login</a>
            </div>
        </section>

        <section class="home-hero-visual" aria-hidden="true">
            <div class="hero-blob hero-blob-a"></div>
            <div class="hero-blob hero-blob-b"></div>

            <div class="preview-wrapper">
                <div class="dashboard-preview">
                    <p class="preview-label">Dashboard Preview</p>
                    <h3>All resources in one place</h3>

                    <div class="preview-card">
                        <strong>Notices</strong>
                        <p>Stay updated with the latest announcements.</p>
                    </div>
                    <div class="preview-card">
                        <strong>Handouts</strong>
                        <p>Download study material anytime.</p>
                    </div>
                    <div class="preview-card">
                        <strong>Results</strong>
                        <p>Check results quickly from one dashboard.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<section class="about-bvassist-section">
    <div class="about-bvassist-inner">
        <div class="about-bvassist-visual">
            <div class="about-bvassist-image-frame">
                <img src="../assets/campus.jpeg" alt="Banasthali Vidyapith campus">
            </div>
        </div>

        <div class="about-bvassist-copy">
            <span class="section-badge">About BV Assist</span>
            <h2>About BV Assist</h2>
            <p>
                BV Assist is a smart academic portal that brings notices, results, handouts, and
                timetable updates into one easy-to-use space for the Banasthali Vidyapith community.
            </p>
            <p>
                It helps students and faculty stay informed, reduce manual follow-ups, and access
                important academic resources without switching between multiple systems.
            </p>
        </div>
    </div>
</section>

<section class="about-section">
    <div class="about-inner">
        <div class="about-copy">
            <span class="section-badge">About</span>
            <h2>About Banasthali Vidyapith</h2>
            <p>
                Banasthali Vidyapith is one of India's premier women-only residential universities,
                known for its strong focus on values, discipline, and holistic education.
            </p>
            <p>
                BV Assist supports the academic experience by bringing notices, handouts, results,
                and timetable updates into one simple, easy-to-use portal for students and faculty.
            </p>
            <p>
                The platform is designed to make everyday communication faster, clearer, and more
                accessible across the campus community.
            </p>
        </div>

        <div class="about-visual">
            <div class="about-image-frame">
                <img src="../assets/logo.png" alt="Banasthali Vidyapith logo">
            </div>
        </div>
    </div>
</section>

<section class="benefits-section">
    <div class="benefits-inner">
        <div class="benefits-header">
            <span class="section-badge">Benefits</span>
            <h2>Why BV Assist helps</h2>
            <p>
                A single place for the core academic actions students and faculty need every day.
            </p>
        </div>

        <div class="benefits-grid">
            <div class="benefit-card">
                <span class="benefit-icon">🎯</span>
                <h3>Centralized academic access</h3>
            </div>
            <div class="benefit-card">
                <span class="benefit-icon">⏱️</span>
                <h3>Saves time</h3>
            </div>
            <div class="benefit-card">
                <span class="benefit-icon">💬</span>
                <h3>Easy communication</h3>
            </div>
            <div class="benefit-card">
                <span class="benefit-icon">🗂️</span>
                <h3>Organized resources</h3>
            </div>
        </div>
    </div>
</section>

<script>
function goToLogin(role){
    window.location.href = "../frontend/login.php?role=" + role;
}
</script>

</body>
</html>
