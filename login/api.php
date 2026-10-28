<?php
session_start();

include '../db.php';
include '../admin/admin_scope.php';


$action = $_POST['action'] ?? '';
if ($action == "student") {
    $username = $_POST['email'] ?? '';
    $password = $_POST['pass'] ?? '';
    $type = $_POST['type'] ?? '';

    // Fetch user data
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? AND role = 'student' LIMIT 1");
        if ($stmt === false) {
            echo json_encode(["status" => 500, "message" => "Prepare failed: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;

    // Validate
    if ($user) {
        // Verify password (if hashed)
        if ($password === $user['password']) {

            // check student role
            if ($user['role'] !== 'student') {
                echo "You are not authorized as a student.";
                exit;
            }

            // Save session
            $_SESSION["user_id"] = $user['user_id'];
            $_SESSION["username"] = $user['username'];
            $_SESSION["role"] = $user['role'];

            echo json_encode([
                "status" => 200,
                "message" => "success"
            ]);
        } else {
            echo json_encode([
                "status" => 400,
                "message" => "Invalid username or password"
            ]);
        }
    } else {
        echo "Invalid Student ID!";
    }
}

if ($action == "faculty") {
    $email = $_POST['email'] ?? '';
$pass = $_POST['pass'] ?? '';
$type = $_POST['type'] ?? '';
$dept = $_POST['selected_dept'] ?? '';

if ($type !== "faculty") {
    echo json_encode(["status" => 400, "message" => "Invalid request type"]);
    exit;
}

// Query user
$stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? LIMIT 1");
if ($stmt === false) {
    echo json_encode(["status" => 500, "message" => "Prepare failed: " . $conn->error]);
    exit;
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;

if ($user) {

    // Password check (assuming hashed)
    if ($pass==$user['password']) {

        $_SESSION['faculty_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        
        $_SESSION['role'] = $user['role'];
        $genderScope = get_hostel_gender_scope_for_role($user['role']);
        if ($genderScope !== null) {
            $_SESSION['hostel_gender_scope'] = $genderScope;
        } else {
            unset($_SESSION['hostel_gender_scope']);
        }

        echo json_encode(["status" => 200,"role" => $user['role'], "message" => "Login successful"]);
    } else {
        echo json_encode(["status" => 400, "message" => "Incorrect password"]);
    }
} 
}
