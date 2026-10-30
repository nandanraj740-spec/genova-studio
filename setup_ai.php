<?php
// Setup script for real AI image generation
echo "<h1>🚀 Real AI Image Generation Setup</h1>";
echo "<p>This will help you generate REAL AI images using Stable Diffusion.</p>";

echo "<h2>Option 1: Local Stable Diffusion (Recommended)</h2>";
echo "<p>For real AI images, you need to run Stable Diffusion locally on your computer.</p>";

echo "<h3>Step 1: Install Automatic1111's Stable Diffusion WebUI</h3>";
echo "<ol>";
echo "<li>Download and install <strong>Python 3.10</strong> from <a href='https://python.org'>python.org</a></li>";
echo "<li>Download the WebUI from: <a href='https://github.com/AUTOMATIC1111/stable-diffusion-webui'>GitHub</a></li>";
echo "<li>Extract to a folder (e.g., <code>C:\\AI\\stable-diffusion-webui</code>)</li>";
echo "<li>Run <code>webui-user.bat</code> to start</li>";
echo "<li>Wait for it to download models (first run takes time)</li>";
echo "<li>Open <code>http://127.0.0.1:7860</code> in your browser</li>";
echo "</ol>";

echo "<h3>Step 2: Test Your Local AI</h3>";
echo "<p>Once running, try generating an image in the WebUI with prompt: 'beautiful sunset over mountains'</p>";

echo "<h2>Option 2: Use Online AI API (If Local Doesn't Work)</h2>";
echo "<p>If you can't run local AI, we can try online services:</p>";

echo "<h3>Hugging Face API</h3>";
echo "<p>Create account at <a href='https://huggingface.co'>huggingface.co</a> and get an API token.</p>";

echo "<h3>OpenAI DALL-E API</h3>";
echo "<p>If you have an OpenAI API key, we can use DALL-E.</p>";

echo "<h2>Current Status</h2>";
echo "<p>Your app is currently using placeholder images because external APIs are blocked.</p>";
echo "<p>Once you have a working AI model, the 'Local AI Art Generator' will create real AI images!</p>";

echo "<h2>Quick Test</h2>";
echo "<p>Visit: <a href='working_ai.php?demo=1'>working_ai.php?demo=1</a> to see current artistic generation.</p>";

if (isset($_GET['check_local'])) {
    echo "<h2>Checking Local AI Connection</h2>";
    
    $ch = curl_init("http://127.0.0.1:7860/sdapi/v1/txt2img");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "prompt" => "test",
        "steps" => 1
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "<p style='color:green'>✅ Local Stable Diffusion is running!</p>";
        echo "<p>You can now generate real AI images.</p>";
    } else {
        echo "<p style='color:red'>❌ Local Stable Diffusion not found.</p>";
        echo "<p>Please install and start Automatic1111's WebUI.</p>";
    }
}

echo "<p><a href='?check_local=1'>Check if Local AI is Running</a></p>";
?>
