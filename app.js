import { config } from './config.js';

const domElements = {
    promptInput: null,
    generateBtn: null,
    modelSelect: null,
    ratioSelect: null,
    loadingAnimation: null,
    resultContainer: null,
    resultImage: null,
    downloadBtn: null,
    shareBtn: null,
    exampleBtn: null
};
const versionMap = {
  "black-forest-labs/FLUX.1-dev": "placeholder_hash_flux1dev", // Replace with actual hash
  "stabilityai/stable-diffusion-xl-base-1.0": "f1d3b4c2b7f5dcdc0eaa1d1e1c1a2ba9e3b1b00cfedb2625a08928fdf0f9db44",
  "stabilityai/stable-diffusion-3-medium-diffusers": "placeholder_hash_sd3medium", // Replace with actual hash
  "runwayml/stable-diffusion-v1-5": "d5dbf1ed6ce7e75fbffb70e0dfd14f5ce1b1d6bcf91a4db0a1e4506f7b79f433",
  "CompVis/stable-diffusion-v1-4": "d22b5e8a040d1b0032d1c3f7a6c39ddcaadf331f5fc00e79126c38cbd63d0c4e",
  "stabilityai/stable-diffusion-2-1": "3edcfbfecf9e47e671cc0f9d2d5b6e5c7a8c0b5fc7b02c07d89a0ed655f2f4e5",
  "prompthero/openjourney": "9936c78e796c52d06f45b1f18e2cdcb0e4a7a67aebf5d718a6ad2d7dbd4ed211",
  "black-forest-labs/FLUX.1-schnell": "bf53bdb93d739c9c915091cfa5f49ca662d11273a5eb30e7a2ec1939bcf27a00"
};


function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    const promptSection = document.querySelector('.prompt-section');
    if (promptSection.querySelector('.error-message')) {
        promptSection.querySelector('.error-message').remove();
    }
    promptSection.appendChild(errorDiv);
    setTimeout(() => errorDiv.remove(), 3000);
}

function validateFields() {
    const promptText = domElements.promptInput?.value?.trim() || '';
    const modelValue = domElements.modelSelect?.value;
    const ratioValue = domElements.ratioSelect?.value;
    if (!promptText) return showError('Please describe what image you want to generate'), false;
    if (!modelValue) return showError('Please select an AI model'), false;
    if (!ratioValue) return showError('Please select an aspect ratio'), false;
    return true;
}

async function generateImage(prompt, model, ratio) {
    try {
        const requestBody = { inputs: prompt };

        // For now, skip sending `aspect_ratio` unless needed per model
        // You can later enhance this by model-specific logic

        const response = await fetch(`${config.API_BASE_URL}${model}`, {
            method: 'POST',
            headers: {
                Authorization: `Bearer ${config.HUGGING_FACE_API_KEY}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });

        if (!response.ok) {
            throw new Error(`API request failed: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');

        if (contentType.includes('application/json')) {
            const data = await response.json();

            if (data.error) throw new Error(data.error);

            if (data.image_url) return data.image_url;
            if (data.data && data.data[0]?.url) return data.data[0].url;
            if (data.data && data.data[0]?.b64_json) {
                return `data:image/png;base64,${data.data[0].b64_json}`;
            }

            throw new Error('Unexpected JSON structure from API');
        } else {
            const blob = await response.blob();
            return URL.createObjectURL(blob);
        }
    } catch (error) {
        console.error('Image generation error:', error);
        throw error;
    }
}

export async function handleGenerate(event) {
    if (event) event.preventDefault();
    if (!validateFields()) return;

    domElements.loadingAnimation.style.display = 'block';
    domElements.resultContainer.style.display = 'none';
    domElements.generateBtn.disabled = true;

    try {
        const imageUrl = await generateImage(
            domElements.promptInput.value.trim(),
            domElements.modelSelect.value,
            domElements.ratioSelect.value
        );
        domElements.resultImage.src = imageUrl;
        domElements.resultContainer.style.display = 'block';
    } catch (err) {
        showError('Failed to generate image. Try a different model.');
    } finally {
        domElements.loadingAnimation.style.display = 'none';
        domElements.generateBtn.disabled = false;
    }
}

window.handleDownload = () => {
    const resultImage = document.getElementById('result-image');
    if (resultImage && resultImage.src) {
        const link = document.createElement('a');
        link.href = resultImage.src;
        link.download = 'generated-image.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

window.handleShare = () => {
    const resultImage = document.getElementById('result-image');
    if (resultImage && resultImage.src) {
        if (navigator.share) {
            navigator.share({
                title: 'My Generated Image',
                text: 'Check this out!',
                url: resultImage.src
            });
        } else {
            navigator.clipboard.writeText(resultImage.src);
            alert('Image URL copied to clipboard!');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    domElements.promptInput = document.querySelector("#prompt");
    domElements.generateBtn = document.querySelector("#generate-btn");
    domElements.modelSelect = document.getElementById("model-select");
    domElements.ratioSelect = document.getElementById("ratio-select");
    domElements.loadingAnimation = document.querySelector(".loading-animation");
    domElements.resultContainer = document.querySelector(".result-container");
    domElements.resultImage = document.querySelector("#result-image");
    domElements.downloadBtn = document.querySelector("#download-btn");
    domElements.shareBtn = document.querySelector("#share-btn");
    domElements.exampleBtn = document.querySelector("#example-btn");

    window.handleGenerate = handleGenerate;

    domElements.promptInput?.addEventListener('input', () => {
        domElements.generateBtn.disabled = domElements.promptInput.value.trim().length < 10;
    });

    domElements.modelSelect?.addEventListener('change', () => {
        domElements.generateBtn.disabled = !validateFields();
    });

    domElements.ratioSelect?.addEventListener('change', () => {
        domElements.generateBtn.disabled = !validateFields();
    });
});
