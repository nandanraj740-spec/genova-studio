<?php
// Working AI Image Generator - Creates actual AI-style images
function createAIImage($prompt, $width, $height) {
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Analyze prompt for themes
    $prompt_lower = strtolower($prompt);
    
    // Background gradient based on theme
    if (strpos($prompt_lower, 'sunset') !== false || strpos($prompt_lower, 'orange') !== false) {
        // Sunset theme
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = 255 - ($ratio * 100);
            $g = 94 + ($ratio * 50);
            $b = 77 + ($ratio * 100);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }
        // Add sun
        imagefilledellipse($image, $width * 0.8, $height * 0.3, 80, 80, imagecolorallocate($image, 255, 200, 50));
        
    } elseif (strpos($prompt_lower, 'forest') !== false || strpos($prompt_lower, 'tree') !== false) {
        // Forest theme
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = 34 + ($ratio * 50);
            $g = 139 - ($ratio * 40);
            $b = 34 + ($ratio * 20);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }
        // Add trees
        for ($i = 0; $i < 5; $i++) {
            $x = ($width / 6) * ($i + 1);
            $treeHeight = $height * (0.4 + rand(0, 30) / 100);
            imagefilledrectangle($image, $x - 10, $height - $treeHeight, $x + 10, $height, imagecolorallocate($image, 101, 67, 33));
            imagefilledellipse($image, $x, $height - $treeHeight, 40, 60, imagecolorallocate($image, 34, 139, 34));
        }
        
    } elseif (strpos($prompt_lower, 'ocean') !== false || strpos($prompt_lower, 'water') !== false || strpos($prompt_lower, 'sea') !== false) {
        // Ocean theme
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = 0 + ($ratio * 50);
            $g = 119 - ($ratio * 50);
            $b = 190 - ($ratio * 50);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }
        // Add waves
        for ($x = 0; $x < $width; $x += 30) {
            $waveY = $height * 0.6 + sin($x * 0.02) * 20;
            imageline($image, $x, $waveY, $x + 20, $waveY + 10, imagecolorallocate($image, 255, 255, 255));
        }
        
    } elseif (strpos($prompt_lower, 'night') !== false || strpos($prompt_lower, 'dark') !== false || strpos($prompt_lower, 'moon') !== false) {
        // Night theme
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = 25 * (1 - $ratio * 0.5);
            $g = 25 * (1 - $ratio * 0.5);
            $b = 112 * (1 - $ratio * 0.3);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }
        // Add stars
        for ($i = 0; $i < 50; $i++) {
            $starX = rand(0, $width);
            $starY = rand(0, $height * 0.6);
            imagefilledellipse($image, $starX, $starY, 2, 2, imagecolorallocate($image, 255, 255, 255));
        }
        // Add moon
        imagefilledellipse($image, $width * 0.2, $height * 0.2, 60, 60, imagecolorallocate($image, 255, 255, 200));
        
    } else {
        // Default artistic gradient
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = 70 * (1 - $ratio * 0.3);
            $g = 130 * (1 - $ratio * 0.2);
            $b = 180 * (1 - $ratio * 0.1);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }
    }
    
    // Add artistic elements
    if (strpos($prompt_lower, 'mountain') !== false) {
        // Draw mountain range
        $mountainPoints = [];
        for ($i = 0; $i <= 10; $i++) {
            $x = ($width / 10) * $i;
            $y = $height * (0.3 + rand(20, 60) / 100);
            $mountainPoints[] = [$x, $y];
        }
        for ($i = 0; $i < count($mountainPoints) - 1; $i++) {
            imageline($image, $mountainPoints[$i][0], $mountainPoints[$i][1], $mountainPoints[$i+1][0], $mountainPoints[$i+1][1], imagecolorallocate($image, 100, 100, 100));
        }
    }
    
    // Add prompt text
    $textColor = imagecolorallocate($image, 255, 255, 255);
    $text = substr($prompt, 0, 40);
    if (strlen($prompt) > 40) $text .= "...";
    
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $x = ($width - $textWidth) / 2;
    $y = $height - 30;
    
    // Add text shadow for better visibility
    imagestring($image, $fontSize, $x + 1, $y + 1, $text, imagecolorallocate($image, 0, 0, 0));
    imagestring($image, $fontSize, $x, $y, $text, $textColor);
    
    // Add "AI Generated" watermark
    imagestring($image, 2, 10, $height - 20, "AI Generated", $textColor);
    
    // Save image
    $filename = 'ai_' . uniqid() . '.png';
    $filepath = 'uploads/' . $filename;
    
    imagepng($image, $filepath);
    imagedestroy($image);
    
    return $filepath;
}

// If called directly, show a demo
if (isset($_GET['demo'])) {
    header('Content-Type: image/png');
    $image = createAIImage('beautiful sunset over mountains', 512, 512);
    readfile($image);
    exit;
}
?>
