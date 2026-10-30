<?php
// Test PHP GD library
echo "<h2>PHP GD Library Test</h2>";

if (extension_loaded('gd') && function_exists('imagecreatetruecolor')) {
    echo "<p style='color:green'>✓ GD Library is available</p>";
    
    // Test creating a simple image
    $img = imagecreatetruecolor(200, 100);
    $bg = imagecolorallocate($img, 70, 130, 180);
    $text = imagecolorallocate($img, 255, 255, 255);
    
    imagefill($img, 0, 0, $bg);
    imagestring($img, 5, 50, 40, "Test Image", $text);
    
    imagepng($img, 'test_gd.png');
    imagedestroy($img);
    
    echo "<p>✓ Test image created: test_gd.png</p>";
    echo "<img src='test_gd.png'>";
} else {
    echo "<p style='color:red'>✗ GD Library is NOT available</p>";
    echo "<p>Please enable GD extension in php.ini</p>";
}

echo "<hr>";

// Test session
echo "<h2>Session Test</h2>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session data: " . print_r($_SESSION, true) . "</p>";

?>
