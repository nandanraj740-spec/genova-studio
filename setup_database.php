<?php
// Auto-setup script for Genova Studio database
// Run this once to create database and tables automatically

echo "<h2>Genova Studio Database Setup</h2>";

// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PORT', '3307');
define('DB_PASSWORD', '');
define('DB_NAME', 'genova_studio');

echo "<p>Connecting to MySQL on port " . DB_PORT . "...</p>";

// Connect to MySQL without database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, null, DB_PORT);

if (!$conn) {
    die("<p style='color:red'><strong>Connection failed:</strong> " . mysqli_connect_error() . "</p>");
}

echo "<p style='color:green'>✓ Connected to MySQL successfully</p>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    echo "<p style='color:green'>✓ Database '" . DB_NAME . "' created or already exists</p>";
} else {
    die("<p style='color:red'><strong>Error creating database:</strong> " . mysqli_error($conn) . "</p>");
}

// Select database
if (!mysqli_select_db($conn, DB_NAME)) {
    die("<p style='color:red'><strong>Could not select database:</strong> " . mysqli_error($conn) . "</p>");
}

echo "<p style='color:green'>✓ Database selected</p>";

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color:green'>✓ Users table created</p>";
} else {
    echo "<p style='color:orange'>⚠ Users table error: " . mysqli_error($conn) . "</p>";
}

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

if (mysqli_query($conn, $sql)) {
    echo "<p style='color:green'>✓ Generations table created</p>";
} else {
    echo "<p style='color:orange'>⚠ Generations table error: " . mysqli_error($conn) . "</p>";
}

// Create rate_limits table
$sql = "CREATE TABLE IF NOT EXISTS rate_limits (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    generation_count INT DEFAULT 0,
    last_generation TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color:green'>✓ Rate limits table created</p>";
} else {
    echo "<p style='color:orange'>⚠ Rate limits table error: " . mysqli_error($conn) . "</p>";
}

echo "<h3 style='color:green'>✅ Setup Complete!</h3>";
echo "<p><a href='index.html'>Go to Genova Studio</a></p>";

mysqli_close($conn);
?>
