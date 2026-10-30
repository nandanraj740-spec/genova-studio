<?php
session_start();

require_once "config.php";

// Ensure this file only processes POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    json_error_response("Invalid request method");
}

$username = isset($_POST["username"]) ? trim($_POST["username"]) : '';
$email = isset($_POST["email"]) ? trim($_POST["email"]) : '';
$password = isset($_POST["password"]) ? trim($_POST["password"]) : '';

debug_log("Received POST request for signup");
debug_log("Username: " . $username);
debug_log("Email: " . $email);
debug_log("Password length: " . strlen($password));

// Validate input
if (empty($username)) {
    json_error_response("Please enter username.");
}
if (strlen($username) < 3) {
    json_error_response("Username must be at least 3 characters long.");
}

if (empty($email)) {
    json_error_response("Please enter email.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error_response("Please enter a valid email address.");
}

if (empty($password)) {
    json_error_response("Please enter password.");
}
if (strlen($password) < 6) {
    json_error_response("Password must be at least 6 characters long.");
}

debug_log("Input validation passed");

// Check if username already exists
$sql = "SELECT id FROM users WHERE username = ?";
debug_log("Checking for existing username: " . $username);

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    if (!mysqli_stmt_execute($stmt)) {
        json_error_response("Error checking username: " . mysqli_error($conn));
    }
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        json_error_response("This username is already taken.");
    }
    mysqli_stmt_close($stmt);
} else {
    json_error_response("Database error: " . mysqli_error($conn));
}

// Check if email already exists
$sql = "SELECT id FROM users WHERE email = ?";
debug_log("Checking for existing email: " . $email);

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $email);
    if (!mysqli_stmt_execute($stmt)) {
        json_error_response("Error checking email: " . mysqli_error($conn));
    }
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        json_error_response("This email is already registered.");
    }
    mysqli_stmt_close($stmt);
} else {
    json_error_response("Database error: " . mysqli_error($conn));
}

// Proceed with registration
$sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
debug_log("Attempting to insert new user");

if ($stmt = mysqli_prepare($conn, $sql)) {
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    debug_log("Password hashed successfully");
    
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        debug_log("User inserted successfully");
        json_success_response([
            "message" => "Registration successful! Please login."
        ]);
    }
    
    json_error_response("Error creating account: " . mysqli_error($conn));
}

json_error_response("Database error: " . mysqli_error($conn));
?> 