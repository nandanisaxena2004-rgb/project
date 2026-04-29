<?php

function bvassist_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function bvassist_current_role(): string
{
    return strtolower(trim((string) ($_SESSION['role'] ?? '')));
}

function bvassist_current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function bvassist_login_redirect(): void
{
    header("Location: ../frontend/login.php");
    exit();
}

function bvassist_dashboard_for_role(string $role): string
{
    return match ($role) {
        'admin' => 'admin_dashboard.php',
        'faculty' => 'faculty_dashboard.php',
        'student' => 'student_dashboard.php',
        default => '../frontend/login.php',
    };
}

function bvassist_redirect_for_role(string $role): void
{
    $target = bvassist_dashboard_for_role($role);
    header("Location: " . $target);
    exit();
}

function bvassist_require_login(): void
{
    bvassist_start_session();

    if (empty($_SESSION['email']) || bvassist_current_role() === '' || bvassist_current_user_id() <= 0) {
        bvassist_login_redirect();
    }
}

function bvassist_require_role(array $allowedRoles): void
{
    bvassist_require_login();

    $role = bvassist_current_role();
    $allowedRoles = array_map('strtolower', $allowedRoles);

    if (!in_array($role, $allowedRoles, true)) {
        if (in_array($role, ['admin', 'faculty', 'student'], true)) {
            bvassist_redirect_for_role($role);
        }

        bvassist_login_redirect();
    }
}
