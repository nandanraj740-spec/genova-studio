// Genova Studio Configuration
// Copy this file to config.js and add your API keys

export const config = {
    // Hugging Face API (Free tier available - get token at huggingface.co/settings/tokens)
    HUGGING_FACE_API_KEY: 'your_huggingface_token_here',
    API_BASE_URL: 'https://api-inference.huggingface.co/models/',
    
    // Alternative: Pollinations AI (Free, no API key required)
    // Uncomment below to use Pollinations (completely free)
    // USE_POLLINATIONS: true,
    // POLLINATIONS_BASE_URL: 'https://image.pollinations.ai/prompt/',
    
    // Alternative: Stable Diffusion API (Free tier available)
    // SD_API_KEY: 'your_sd_api_key_here',
    // SD_API_URL: 'https://api.stability.ai/v2beta/stable-image/generate/sd3',
};
