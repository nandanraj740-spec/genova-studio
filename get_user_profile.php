<?php
session_start();
require_once "config.php";

// Set headers for JSON response
header('Content-Type: application/json');

// Get JSON data from request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['username'])) {
    json_error_response('Username is required');
}

$username = trim($data['username']);

// Prepare SQL statement to get user data
$sql = "SELECT id, username, email, created_at FROM users WHERE username = ?";

if (!$stmt = mysqli_prepare($conn, $sql)) {
    json_error_response('Database error: ' . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "s", $username);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    json_error_response('Database error: ' . mysqli_error($conn));
}

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    // Return user data
    json_success_response([
        'username' => $row['username'],
        'email' => $row['email'],
        'created_at' => $row['created_at'],
        'id' => $row['id']
    ]);
}

mysqli_stmt_close($stmt);
json_error_response('User not found');

mysqli_close($conn);
?> 