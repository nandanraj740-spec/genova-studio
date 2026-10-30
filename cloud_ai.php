<?php
// Free Cloud AI Image Generators

function generateHuggingFaceImage($prompt, $width, $height) {
    // Hugging Face Inference API - Free tier available
    $apiUrl = "https://api-inference.huggingface.co/models/stabilityai/stable-diffusion-2-1";
    
    $data = [
        "inputs" => $prompt,
        "parameters" => [
            "width" => $width,
            "height" => $height,
            "num_inference_steps" => 20,
            "guidance_scale" => 7.5
        ]
    ];
    
    $jsonData = json_encode($data);
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $filename = 'hf_' . uniqid() . '.png';
        $filepath = 'uploads/' . $filename;
        
        if (file_put_contents($filepath, $response)) {
            return $filepath;
        }
    }
    
    return false;
}

function generateReplicateImage($prompt, $width, $height) {
    // Replicate API - Free tier available
    $apiUrl = "https://api.replicate.com/v1/predictions";
    
    $data = [
        "version" => "db21e45d3f7023abc2a46ee38a23973f6dce16bb082a930b0c49861f96d1e5bf", // Stable Diffusion 2.1
        "input" => [
            "prompt" => $prompt,
            "width" => $width,
            "height" => $height,
            "num_inference_steps" => 20,
            "guidance_scale" => 7.5
        ]
    ];
    
    $jsonData = json_encode($data);
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Token r8_1234567890abcdef' // Placeholder - user needs to add their key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201 && $response) {
        $result = json_decode($response, true);
        if (isset($result['urls']['get'])) {
            // For Replicate, we need to poll for completion
            return pollReplicateResult($result['urls']['get']);
        }
    }
    
    return false;
}

function pollReplicateResult($resultUrl) {
    // Poll for 60 seconds max
    for ($i = 0; $i < 12; $i++) {
        sleep(5);
        
        $ch = curl_init($resultUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token r8_1234567890abcdef'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if (isset($result['status']) && $result['status'] === 'succeeded') {
                if (isset($result['output']) && is_array($result['output'])) {
                    // Download the image
                    $imageUrl = $result['output'][0];
                    $imageData = file_get_contents($imageUrl);
                    
                    if ($imageData) {
                        $filename = 'rep_' . uniqid() . '.png';
                        $filepath = 'uploads/' . $filename;
                        
                        if (file_put_contents($filepath, $imageData)) {
                            return $filepath;
                        }
                    }
                }
            } elseif (isset($result['status']) && $result['status'] === 'failed') {
                break; // Stop polling if failed
            }
        }
    }
    
    return false;
}

function generateStabilityAIImage($prompt, $width, $height) {
    // Stability AI API - Free tier available
    $apiUrl = "https://api.stability.ai/v1/generation/stable-diffusion-v1-6/text-to-image";
    
    $data = [
        "text_prompts" => [
            [
                "text" => $prompt,
                "weight" => 1
            ]
        ],
        "cfg_scale" => 7,
        "height" => $height,
        "width" => $width,
        "samples" => 1,
        "steps" => 20
    ];
    
    $jsonData = json_encode($data);
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer sk-1234567890abcdef' // Placeholder - user needs to add their key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['artifacts'][0]['base64'])) {
            $imageData = base64_decode($result['artifacts'][0]['base64']);
            $filename = 'stability_' . uniqid() . '.png';
            $filepath = 'uploads/' . $filename;
            
            if (file_put_contents($filepath, $imageData)) {
                return $filepath;
            }
        }
    }
    
    return false;
}

function generateCloudAIImage($api, $prompt, $width, $height) {
    switch ($api) {
        case 'huggingface':
            return generateHuggingFaceImage($prompt, $width, $height);
        case 'replicate':
            return generateReplicateImage($prompt, $width, $height);
        case 'stability':
            return generateStabilityAIImage($prompt, $width, $height);
        default:
            // Try all APIs in order
            $result = generateHuggingFaceImage($prompt, $width, $height);
            if ($result) return $result;
            
            $result = generateReplicateImage($prompt, $width, $height);
            if ($result) return $result;
            
            $result = generateStabilityAIImage($prompt, $width, $height);
            if ($result) return $result;
            
            // If all fail, return false
            return false;
    }
}

// Test function
if (isset($_GET['test_hf'])) {
    header('Content-Type: image/png');
    $result = generateHuggingFaceImage('a beautiful sunset over mountains', 512, 512);
    if ($result) {
        readfile($result);
    } else {
        echo "Failed to generate image";
    }
    exit;
}
?>
