<?php
// Suppress ALL error output - log only
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

ob_start();
session_start();

// Set JSON header
header('Content-Type: application/json');

// Debug log function
function debug_log($message) {
    error_log('[Genova] ' . print_r($message, true));
}

// Function to return JSON error response
function json_error_response($message) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Function to return JSON success response
function json_success_response($data = []) {
    ob_clean();
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PORT', '3307');
define('DB_PASSWORD', '');
define('DB_NAME', 'genova_studio');

debug_log("Attempting to connect to MySQL...");

// Step 1: Connect to MySQL without selecting a database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, null, DB_PORT);

// Check connection
if (!$conn) {
    debug_log("Connection failed: " . mysqli_connect_error());
    json_error_response("Database connection failed. Please try again later.");
}

debug_log("Connected to MySQL successfully");

// Step 2: Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (!mysqli_query($conn, $sql)) {
    debug_log("Error creating database: " . mysqli_error($conn));
    json_error_response("Database creation error. Please try again later.");
}

debug_log("Database created or already exists");

// Step 3: Select the database
if (!mysqli_select_db($conn, DB_NAME)) {
    debug_log("Could not select database: " . mysqli_error($conn));
    json_error_response("Database error. Please try again later.");
}

debug_log("Selected database successfully");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $sql)) {
    debug_log("Error creating users table: " . mysqli_error($conn));
    json_error_response("Users table creation failed.");
}

debug_log("Users table OK");

// Create generations table
$sql = "CREATE TABLE IF NOT EXISTS generations (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    prompt TEXT NOT NULL,
    model VARCHAR(100) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    width INT DEFAULT 1024,
    height INT DEFAULT 1024,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $sql)) {
    debug_log("Error creating generations table: " . mysqli_error($conn));
    json_error_response("Generations table creation failed.");
}

debug_log("Generations table OK");

// Create rate_limits table
$sql = "CREATE TABLE IF NOT EXISTS rate_limits (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    generation_count INT DEFAULT 0,
    last_generation TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $sql)) {
    debug_log("Error creating rate_limits table: " . mysqli_error($conn));
    json_error_response("Rate limits table creation failed.");
}

debug_log("Rate limits table OK");

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

debug_log("Database setup completed successfully");
?>