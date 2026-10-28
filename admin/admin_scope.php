<?php

if (!function_exists('get_current_session_role')) {
    function get_current_session_role(): string
    {
        return $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
    }
}

if (!function_exists('is_super_admin_role')) {
    function is_super_admin_role(?string $role = null): bool
    {
        $role = $role ?? get_current_session_role();
        return $role === 'admin';
    }
}

if (!function_exists('is_gender_admin_role')) {
    function is_gender_admin_role(?string $role = null): bool
    {
        $role = $role ?? get_current_session_role();
        return in_array($role, ['male_admin', 'female_admin'], true);
    }
}

if (!function_exists('is_any_admin_role')) {
    function is_any_admin_role(?string $role = null): bool
    {
        $role = $role ?? get_current_session_role();
        return in_array($role, ['admin', 'male_admin', 'female_admin'], true);
    }
}

if (!function_exists('get_hostel_gender_scope_for_role')) {
    function get_hostel_gender_scope_for_role(?string $role = null): ?string
    {
        $role = $role ?? get_current_session_role();

        if ($role === 'male_admin') {
            return 'Male';
        }
        if ($role === 'female_admin') {
            return 'Female';
        }

        return null;
    }
}

if (!function_exists('is_hostel_id_allowed_for_current_admin')) {
    function is_hostel_id_allowed_for_current_admin(mysqli $conn, int $hostelId): bool
    {
        if ($hostelId <= 0) {
            return false;
        }

        $genderScope = get_hostel_gender_scope_for_role();
        if ($genderScope === null) {
            return true;
        }

        $stmt = $conn->prepare('SELECT hostel_id FROM hostels WHERE hostel_id = ? AND gender = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('is', $hostelId, $genderScope);
        $stmt->execute();
        $result = $stmt->get_result();
        $allowed = $result && $result->num_rows > 0;
        $stmt->close();

        return $allowed;
    }
}

if (!function_exists('normalize_hostel_id_for_current_admin')) {
    function normalize_hostel_id_for_current_admin(mysqli $conn, $hostelId): ?int
    {
        if ($hostelId === null || $hostelId === '') {
            return null;
        }

        $candidate = (int)$hostelId;
        if ($candidate <= 0) {
            return null;
        }

        if (!is_hostel_id_allowed_for_current_admin($conn, $candidate)) {
            return null;
        }

        return $candidate;
    }
}

if (!function_exists('get_default_hostel_id_for_current_admin')) {
    function get_default_hostel_id_for_current_admin(mysqli $conn): ?int
    {
        $genderScope = get_hostel_gender_scope_for_role();
        if ($genderScope === null) {
            return null;
        }

        $stmt = $conn->prepare('SELECT hostel_id FROM hostels WHERE gender = ? ORDER BY hostel_name LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $genderScope);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ? (int)$row['hostel_id'] : null;
    }
}

if (!function_exists('build_hostel_gender_scope_condition')) {
    function build_hostel_gender_scope_condition(string $hostelAlias = 'h'): string
    {
        $genderScope = get_hostel_gender_scope_for_role();
        if ($genderScope === null) {
            return '';
        }

        return " AND {$hostelAlias}.gender = '" . addslashes($genderScope) . "'";
    }
}

if (!function_exists('redirect_if_not_admin_role')) {
    function redirect_if_not_admin_role(): void
    {
        if (!is_any_admin_role()) {
            header('Location: ../login');
            exit;
        }
    }
}
