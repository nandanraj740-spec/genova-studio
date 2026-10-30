<?php
session_start();

require_once "config.php";

// Ensure this file only processes POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    json_error_response("Invalid request method");
}

$username = isset($_POST["username"]) ? trim($_POST["username"]) : '';
$password = isset($_POST["password"]) ? trim($_POST["password"]) : '';

// Debug incoming data
debug_log("Login attempt for username: " . $username);

// Validate input
if (empty($username)) {
    json_error_response("Please enter username.");
}

if (empty($password)) {
    json_error_response("Please enter your password.");
}

// First check if the users table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($check_table) == 0) {
    json_error_response("Database setup incomplete. Please contact administrator.");
}

$sql = "SELECT id, username, email, password FROM users WHERE username = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            debug_log("User found, verifying password");
            debug_log("Stored hash: " . $row['password']);
            debug_log("Provided password: " . $password);
            debug_log("Password length: " . strlen($password));
            
            if (password_verify($password, $row['password'])) {
                debug_log("Password verified successfully");
                
                // Store data in session variables
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $row["id"];
                $_SESSION["username"] = $row["username"];
                $_SESSION["email"] = $row["email"];
                
                json_success_response();
            }
            
            debug_log("Password verification failed");
            json_error_response("Invalid username or password.");
        }
        
        debug_log("No user found with username: " . $username);
        json_error_response("Invalid username or password.");
    }
    
    json_error_response("Database error: " . mysqli_error($conn));
}

json_error_response("Database error: " . mysqli_error($conn));
?> 