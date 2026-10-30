<?php
// Real AI Image Generator using Stable Diffusion API
function generateStableDiffusionImage($prompt, $width, $height) {
    // Try to connect to local Stable Diffusion WebUI API
    $apiUrl = "http://127.0.0.1:7860/sdapi/v1/txt2img";
    
    $data = [
        "prompt" => $prompt,
        "negative_prompt" => "blurry, low quality, deformed",
        "width" => $width,
        "height" => $height,
        "steps" => 20,
        "cfg_scale" => 7,
        "sampler_name" => "Euler a",
        "batch_size" => 1,
        "n_iter" => 1,
        "seed" => -1,
        "subseed" => -1,
        "subseed_strength" => 0,
        "restore_faces" => false,
        "tiling" => false,
        "do_not_save_samples" => false,
        "do_not_save_grid" => false,
        "eta" => 0,
        "denoising_strength" => 0.75,
        "s_min_uncond" => 0,
        "s_tmax" => null,
        "s_tmin" => 0,
        "s_churn" => 0,
        "s_turbo" => 0,
        "s_noise" => 1,
        "override_settings" => null,
        "override_settings_restore_afterwards" => true,
        "refiner_checkpoint" => null,
        "refiner_switch_at" => null,
        "disable_extra_networks" => false,
        "comments" => null,
        "enable_hr" => false,
        "firstphase_width" => 0,
        "firstphase_height" => 0,
        "hr_scale" => 2,
        "hr_upscaler" => null,
        "hr_second_pass_steps" => 0,
        "hr_resize_x" => 0,
        "hr_resize_y" => 0,
        "hr_checkpoint_name" => null,
        "hr_sampler_name" => null,
        "hr_prompt" => "",
        "hr_negative_prompt" => "",
        "force_task_id" => null,
        "scheduler" => null
    ];
    
    $jsonData = json_encode($data);
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['images'][0])) {
            // Save the base64 image
            $imageData = base64_decode($result['images'][0]);
            $filename = 'sd_' . uniqid() . '.png';
            $filepath = 'uploads/' . $filename;
            
            if (file_put_contents($filepath, $imageData)) {
                return $filepath;
            }
        }
    }
    
    // If local API fails, try online fallback
    return generateOnlineAIFallback($prompt, $width, $height);
}

function generateOnlineAIFallback($prompt, $width, $height) {
    // Try a working online API - using a different service that might work
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
        // HuggingFace returns binary image data
        $filename = 'hf_' . uniqid() . '.png';
        $filepath = 'uploads/' . $filename;
        
        if (file_put_contents($filepath, $response)) {
            return $filepath;
        }
    }
    
    // If all else fails, use the artistic generator
    return createAIImage($prompt, $width, $height);
}
?>
