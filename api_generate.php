<?php
require_once 'config.php';

// Secure API Proxy for Image Generation
// This keeps API keys server-side and adds rate limiting

header('Content-Type: application/json');

// Ensure uploads directory exists with proper permissions
$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        debug_log('Failed to create uploads directory');
        json_error_response('Server configuration error: Cannot create uploads folder');
    }
    debug_log('Created uploads directory');
}

// Check if uploads directory is writable
if (!is_writable($uploadsDir)) {
    debug_log('Uploads directory is not writable');
    json_error_response('Server configuration error: Uploads folder is not writable');
}

debug_log("Uploads directory OK: " . $uploadsDir);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$prompt = $input['prompt'] ?? '';
$model = $input['model'] ?? 'pollinations/flux';
$width = $input['width'] ?? 1024;
$height = $input['height'] ?? 1024;

// Get user ID from session - process_login sets 'id'
$userId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

debug_log("Session user_id: " . ($userId ? $userId : 'NOT SET'));
debug_log("Session data: " . print_r($_SESSION, true));

if (empty($prompt)) {
    json_error_response('Prompt is required');
}

if (!$userId) {
    debug_log('Authentication failed - no user_id in session');
    json_error_response('Please login to generate images');
}

// Rate limiting check
$rateLimit = checkRateLimit($conn, $userId);
if (!$rateLimit['allowed']) {
    json_error_response('Rate limit exceeded. Try again in ' . $rateLimit['retry_after'] . ' seconds');
}

// Model configurations without API keys
$modelConfigs = [
    'pollinations/flux' => [
        'type' => 'pollinations',
        'base_url' => 'https://image.pollinations.ai/prompt',
        'params' => ["width" => $width, "height" => $height, "seed" => rand(1, 999999), "nologo" => 'true']
    ],
    'pollinations/turbo' => [
        'type' => 'pollinations',
        'base_url' => 'https://image.pollinations.ai/prompt',
        'params' => ["width" => $width, "height" => $height, "seed" => rand(1, 999999), "nologo" => 'true', "model" => "turbo"]
    ],
    // Local artistic generator (offline mode)
    'local/ai' => [
        'type' => 'local',
        'prompt' => $prompt,
        'width' => $width,
        'height' => $height
    ]
];

$modelConfig = $modelConfigs[$model] ?? $modelConfigs['pollinations/flux'];

try {
    $imageUrl = generateImage($modelConfig, $prompt, $width, $height);
    
    // Save generation to database
    try {
        saveGeneration($conn, $userId, $prompt, $model, $imageUrl, $width, $height);
        // Update rate limit counter
        updateRateLimit($conn, $userId);
    } catch (Exception $dbError) {
        debug_log('DB Error saving generation: ' . $dbError->getMessage());
    }
    
    json_success_response([
        'success' => true,
        'image_url' => 'uploads/' . basename($imageUrl),
        'model' => $model,
        'prompt' => $prompt
    ]);
    
} catch (Exception $e) {
    debug_log('Generation error: ' . $e->getMessage());
    
    // Fallback: generate a placeholder image
    $placeholderUrl = generatePlaceholder($prompt, $width, $height);
    
    try {
        saveGeneration($conn, $userId, $prompt, $model, $placeholderUrl, $width, $height);
    } catch (Exception $dbError) {
        debug_log('DB Error saving placeholder: ' . $dbError->getMessage());
    }
    
    json_success_response([
        'success' => true,
        'image_url' => 'uploads/' . basename($placeholderUrl),
        'model' => $model,
        'prompt' => $prompt,
        'note' => 'API failed, showing placeholder'
    ]);
}

function generatePlaceholder($prompt, $width, $height) {
    debug_log("Generating PNG placeholder for: " . $prompt);
    
    // Create a simple placeholder image with text
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $bgColor = imagecolorallocate($image, 70, 130, 180); // Steel blue
    $textColor = imagecolorallocate($image, 255, 255, 255); // White
    
    // Fill background
    imagefill($image, 0, 0, $bgColor);
    
    // Add text using built-in font
    $text = "AI Generated: " . substr($prompt, 0, 40) . (strlen($prompt) > 40 ? "..." : "");
    $textWidth = imagefontwidth(5) * strlen($text);
    $x = ($width - $textWidth) / 2;
    $y = $height / 2;
    
    imagestring($image, 5, $x, $y, $text, $textColor);
    
    // Save image
    $filename = 'placeholder_' . uniqid() . '.png';
    $filepath = 'uploads/' . $filename;
    
    imagepng($image, $filepath);
    imagedestroy($image);
    
    debug_log("PNG placeholder saved: " . $filepath);
    return $filepath;
}

function generateImage($config, $prompt, $width, $height) {
    switch ($config['type']) {
        case 'local':
            // Offline local artistic fallback
            require_once 'working_ai.php';
            return createAIImage($prompt, $width, $height);
            
        case 'pollinations':
            // Build URL with prompt and parameters
            $url = $config['base_url'] . '/' . urlencode($prompt);
            $params = [];
            foreach ($config['params'] as $key => $value) {
                $params[] = $key . '=' . urlencode($value);
            }
            $fullUrl = $url . '?' . implode('&', $params);
            
            debug_log("Pollinations URL: " . $fullUrl);
            
            $ch = curl_init($fullUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // Strong user agent required for many free endpoints
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: image/jpeg, image/png, image/*;q=0.8, */*;q=0.5'
            ]);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            debug_log("Pollinations HTTP Code: " . $httpCode);
            debug_log("Pollinations CURL Error: " . $curlError);
            
            // Check if response is empty or tiny (like an error text rather than an image)
            if ($httpCode !== 200 || !$imageData || strlen($imageData) < 1000) {
                throw new Exception('Pollinations API failed with HTTP ' . $httpCode . ' Error: ' . $curlError);
            }
            
            // Save to local file
            $filename = 'gen_' . uniqid() . '.jpg';
            $filepath = 'uploads/' . $filename;
            
            debug_log("Attempting to save image to: " . $filepath);
            debug_log("Image data size: " . strlen($imageData) . " bytes");
            
            if (file_put_contents($filepath, $imageData) === false) {
                debug_log("Failed to save image file");
                throw new Exception('Failed to save generated image');
            }
            
            if (!file_exists($filepath)) {
                debug_log("File was not created after save");
                throw new Exception('Image file was not created');
            }
            
            debug_log("Image saved successfully: " . $filepath);
            return $filepath;
            
        default:
            throw new Exception('Unknown model type');
    }
}

function generateCraiyon($prompt) {
    debug_log("Starting Craiyon generation for prompt: " . $prompt);
    
    $ch = curl_init('https://api.craiyon.com/v3');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['prompt' => $prompt]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    debug_log("Craiyon HTTP Code: " . $httpCode);
    debug_log("Craiyon Response: " . $response);
    debug_log("Craiyon CURL Error: " . $curlError);
    
    if ($httpCode !== 200) {
        throw new Exception("Craiyon API failed with HTTP {$httpCode}: {$curlError}");
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Craiyon JSON decode error: " . json_last_error_msg());
    }
    
    if (!isset($data['images']) || !is_array($data['images']) || empty($data['images'])) {
        debug_log("Craiyon response structure: " . print_r($data, true));
        throw new Exception('Craiyon generation failed - no images in response');
    }
    
    // Craiyon returns base64 images
    $imageData = base64_decode($data['images'][0]);
    if ($imageData === false) {
        throw new Exception('Craiyon base64 decode failed');
    }
    
    $filename = 'gen_' . uniqid() . '.png';
    $filepath = 'uploads/' . $filename;
    
    if (file_put_contents($filepath, $imageData) === false) {
        throw new Exception('Failed to save Craiyon image');
    }
    
    debug_log("Craiyon image saved: " . $filepath);
    return $filepath;
}

function generateLeonardo($config, $prompt, $width, $height) {
    // Leonardo AI uses different model IDs - use a default free model
    $modelId = '6bef9e1f-8c0f-4f8e-9f1f-6b1f9e1f8c0f'; // Leonardo Diffusion XL
    
    $payload = [
        'prompt' => $prompt,
        'modelId' => $modelId,
        'width' => $width,
        'height' => $height,
        'num_images' => 1,
        'guidance_scale' => 7
    ];
    
    $ch = curl_init($config['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['api_key'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        // Fallback to Pollinations if Leonardo fails
        debug_log('Leonardo API failed, falling back to Pollinations');
        return generateImage(['type' => 'pollinations', 'url' => 'https://image.pollinations.ai/prompt/' . urlencode($prompt), 'params' => "width={$width}&height={$height}&seed=" . rand(1, 999999) . "&nologo=true"], $prompt, $width, $height);
    }
    
    $data = json_decode($response, true);
    if (!isset($data['generations']['images'][0]['url'])) {
        throw new Exception('Leonardo generation failed');
    }
    
    // Download image from Leonardo
    $imageUrl = $data['generations']['images'][0]['url'];
    $imageData = file_get_contents($imageUrl);
    
    $filename = 'gen_' . uniqid() . '.png';
    $filepath = 'uploads/' . $filename;
    file_put_contents($filepath, $imageData);
    return $filepath;
}

function checkRateLimit($conn, $userId) {
    $stmt = $conn->prepare("SELECT generation_count, last_generation FROM rate_limits WHERE user_id = ? AND DATE(last_generation) = CURDATE()");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dailyLimit = 50; // 50 images per day per user
    
    if ($row = $result->fetch_assoc()) {
        if ($row['generation_count'] >= $dailyLimit) {
            return ['allowed' => false, 'retry_after' => 86400];
        }
    }
    
    return ['allowed' => true];
}

function updateRateLimit($conn, $userId) {
    $stmt = $conn->prepare("INSERT INTO rate_limits (user_id, generation_count, last_generation) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE generation_count = generation_count + 1, last_generation = NOW()");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}

function saveGeneration($conn, $userId, $prompt, $model, $imageUrl, $width, $height) {
    $stmt = $conn->prepare("INSERT INTO generations (user_id, prompt, model, image_url, width, height, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssii", $userId, $prompt, $model, $imageUrl, $width, $height);
    $stmt->execute();
}
