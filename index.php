<?php
session_start();

// If no session at all -> login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['faculty_id'])) {
    header("Location: login/");
    exit;
}

// Identify role
$role = $_SESSION['role'] ?? null;

switch ($role) {
    case 'student':
        header("Location: student/");
        break;

    case 'admin':
        header("Location: admin/");
        break;

    case 'faculty':
        header("Location: faculty/");
        break;

    default:
        // Unknown role -> force login
        header("Location: login/");
        break;
}

exit;
