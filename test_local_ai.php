<?php
// Test local AI generator directly
require_once 'local_ai_generator.php';

header('Content-Type: image/png');
$image = generateLocalArt('sunset over mountains', 512, 512);

// Output the image directly
readfile($image);
?>
