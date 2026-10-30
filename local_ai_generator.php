<?php
// Local AI Image Generator - Uses built-in PHP to create artistic images
function generateLocalArt($prompt, $width, $height) {
    debug_log("Generating local art for: " . $prompt);
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Extract keywords from prompt for color selection
    $keywords = strtolower($prompt);
    $bgColor = [70, 130, 180]; // Default steel blue
    
    // Color mapping based on keywords
    if (strpos($keywords, 'sunset') !== false || strpos($keywords, 'orange') !== false) {
        $bgColor = [255, 94, 77]; // Sunset orange
    } elseif (strpos($keywords, 'forest') !== false || strpos($keywords, 'green') !== false) {
        $bgColor = [34, 139, 34]; // Forest green
    } elseif (strpos($keywords, 'ocean') !== false || strpos($keywords, 'blue') !== false) {
        $bgColor = [0, 119, 190]; // Ocean blue
    } elseif (strpos($keywords, 'night') !== false || strpos($keywords, 'dark') !== false) {
        $bgColor = [25, 25, 112]; // Midnight blue
    } elseif (strpos($keywords, 'fire') !== false || strpos($keywords, 'red') !== false) {
        $bgColor = [178, 34, 34]; // Fire red
    }
    
    // Create gradient background
    for ($y = 0; $y < $height; $y++) {
        $factor = $y / $height;
        $r = $bgColor[0] * (1 - $factor * 0.3);
        $g = $bgColor[1] * (1 - $factor * 0.3);
        $b = $bgColor[2] * (1 - $factor * 0.3);
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $y, $width, $y, $color);
    }
    
    // Add artistic elements based on prompt
    $textColor = imagecolorallocate($image, 255, 255, 255);
    $accentColor = imagecolorallocate($image, 255, 255, 200);
    
    // Add geometric patterns
    if (strpos($keywords, 'mountain') !== false) {
        // Draw mountains
        $points = [
            [$width * 0.2, $height * 0.7],
            [$width * 0.4, $height * 0.3],
            [$width * 0.6, $height * 0.5],
            [$width * 0.8, $height * 0.4],
            [$width, $height * 0.6]
        ];
        for ($i = 0; $i < count($points) - 1; $i++) {
            imageline($image, $points[$i][0], $points[$i][1], $points[$i+1][0], $points[$i+1][1], $accentColor);
        }
    }
    
    if (strpos($keywords, 'sun') !== false || strpos($keywords, 'moon') !== false) {
        // Draw sun/moon
        imagefilledellipse($image, $width * 0.8, $height * 0.2, 60, 60, $accentColor);
    }
    
    if (strpos($keywords, 'water') !== false || strpos($keywords, 'lake') !== false) {
        // Draw water reflection
        for ($x = 0; $x < $width; $x += 20) {
            $waveY = $height * 0.7 + sin($x * 0.05) * 10;
            imageline($image, $x, $waveY, $x + 10, $waveY + 5, $accentColor);
        }
    }
    
    // Add prompt text
    $text = wordwrap(substr($prompt, 0, 50), 20, "\n", true);
    $lines = explode("\n", $text);
    $fontSize = 5;
    $y = $height - 30 - (count($lines) - 1) * 15;
    
    foreach ($lines as $line) {
        $textWidth = imagefontwidth($fontSize) * strlen($line);
        $x = ($width - $textWidth) / 2;
        imagestring($image, $fontSize, $x, $y, $line, $textColor);
        $y += 15;
    }
    
    // Add "AI Generated" watermark
    imagestring($image, 2, 10, 10, "AI Generated", $textColor);
    
    // Save image
    $filename = 'local_art_' . uniqid() . '.png';
    $filepath = 'uploads/' . $filename;
    
    imagepng($image, $filepath);
    imagedestroy($image);
    
    debug_log("Local art saved: " . $filepath);
    return $filepath;
}

// Test the function
if (isset($_GET['test'])) {
    header('Content-Type: image/png');
    $image = imagecreatetruecolor(512, 512);
    $bg = imagecolorallocate($image, 70, 130, 180);
    $text = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $bg);
    imagestring($image, 5, 150, 250, "Local AI Art Generator", $text);
    imagepng($image);
    imagedestroy($image);
    exit;
}
?>
