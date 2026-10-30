<?php
// Simple debug API to test what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug API Test</h2>";

// Check session
session_start();
echo "<p>Session data: " . print_r($_SESSION, true) . "</p>";

// Check POST data
echo "<p>POST data: " . print_r($_POST, true) . "</p>";
echo "<p>JSON input: " . file_get_contents('php://input') . "</p>";

// Check if we can create an image
if (extension_loaded('gd') && function_exists('imagecreatetruecolor')) {
    echo "<p style='color:green'>✓ GD Library available</p>";
    
    // Test creating a simple image
    $img = imagecreatetruecolor(200, 100);
    $bg = imagecolorallocate($img, 70, 130, 180);
    $text = imagecolorallocate($img, 255, 255, 255);
    
    imagefill($img, 0, 0, $bg);
    imagestring($img, 5, 50, 40, "Test Image", $text);
    
    imagepng($img, 'debug_test.png');
    imagedestroy($img);
    
    echo "<p>✓ Test image created: debug_test.png</p>";
    echo "<img src='debug_test.png'>";
} else {
    echo "<p style='color:red'>✗ GD Library not available</p>";
}

// Test local AI function
if (file_exists('local_ai_generator.php')) {
    echo "<p style='color:green'>✓ local_ai_generator.php exists</p>";
    
    try {
        require_once 'local_ai_generator.php';
        if (function_exists('generateLocalArt')) {
            echo "<p style='color:green'>✓ generateLocalArt function exists</p>";
            
            $result = generateLocalArt('test sunset', 256, 256);
            echo "<p style='color:green'>✓ Local AI generated: $result</p>";
            
            if (file_exists($result)) {
                echo "<img src='$result' width='256' height='256'>";
            } else {
                echo "<p style='color:red'>✗ Generated file not found</p>";
            }
        } else {
            echo "<p style='color:red'>✗ generateLocalArt function not found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>✗ local_ai_generator.php not found</p>";
}

?>
