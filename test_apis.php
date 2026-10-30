<?php
// Simple test for image generation APIs
echo "<h2>Testing AI Image APIs</h2>";

// Test Pollinations
echo "<h3>Testing Pollinations...</h3>";
$url = "https://image.pollinations.ai/prompt/a%20cat?width=256&height=256&seed=123&nologo=true";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<p>CURL Error: $curlError</p>";
echo "<p>Response size: " . strlen($response) . " bytes</p>";

if ($httpCode == 200 && strlen($response) > 1000) {
    echo "<p style='color:green'>✓ Pollinations working!</p>";
    file_put_contents('test_pollinations.png', $response);
    echo "<img src='test_pollinations.png' width='256' height='256'>";
} else {
    echo "<p style='color:red'>✗ Pollinations failed</p>";
}

echo "<hr>";

// Test Craiyon
echo "<h3>Testing Craiyon...</h3>";
$ch = curl_init('https://api.craiyon.com/v3');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['prompt' => 'a cat']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<p>CURL Error: $curlError</p>";
echo "<p>Response: " . substr($response, 0, 200) . "...</p>";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (isset($data['images']) && !empty($data['images'])) {
        echo "<p style='color:green'>✓ Craiyon working!</p>";
        $imageData = base64_decode($data['images'][0]);
        file_put_contents('test_craiyon.png', $imageData);
        echo "<img src='test_craiyon.png' width='256' height='256'>";
    } else {
        echo "<p style='color:red'>✗ Craiyon response invalid</p>";
    }
} else {
    echo "<p style='color:red'>✗ Craiyon failed</p>";
}
?>
