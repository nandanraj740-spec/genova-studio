// Genova Studio - Enhanced Image Generator
// Uses secure PHP proxy with rate limiting and history tracking

const API_BASE_URL = 'api_generate.php';
const API_HISTORY_URL = 'api_get_generations.php';

// Enhanced model map with cloud AI models
const modelMap = {
    // 100% Free & No-Key AI Models
    "pollinations/flux": { name: "FLUX AI (High Quality)", type: "pollinations", free: true },
    "pollinations/turbo": { name: "Stable Diffusion Turbo (Fast)", type: "pollinations", free: true },
    
    // Offline local fallback
    "local/ai": { name: "Local PHP Art (Offline Mode)", type: "local", free: true }
};

// Global variables
let prompt, generateBtn, resultImage, loadingAnimation, resultContainer;
let modelSelect, countSelect, ratioSelect, downloadBtn, shareBtn, galleryGrid;
let isGenerating = false;
let currentGenerations = [];

function showError(message, type = 'error') {
    const errorDiv = document.createElement('div');
    errorDiv.className = `error-message ${type}`;
    errorDiv.textContent = message;
    const promptSection = document.querySelector('.prompt-section');
    if (promptSection) {
        promptSection.querySelector('.error-message')?.remove();
        promptSection.appendChild(errorDiv);
        setTimeout(() => errorDiv.remove(), 5000);
    }
}

function showSuccess(message) {
    showError(message, 'success');
}

function validateFields() {
    const isValid = prompt && prompt.value.trim().length >= 3 && modelSelect && modelSelect.value && ratioSelect && ratioSelect.value;
    if (generateBtn) generateBtn.disabled = !isValid || isGenerating;
    return isValid;
}

function getDimensions() {
    const ratio = ratioSelect.value;
    switch(ratio) {
        case '16/9': return { width: 1024, height: 576 };
        case '9/16': return { width: 576, height: 1024 };
        case '4/3': return { width: 1024, height: 768 };
        case '3/4': return { width: 768, height: 1024 };
        default: return { width: 1024, height: 1024 };
    }
}

async function generateSingleImage(promptText, modelId, index = 0) {
    const { width, height } = getDimensions();
    
    const response = await fetch(API_BASE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            prompt: promptText,
            model: modelId,
            width: width,
            height: height
        })
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || `HTTP Error: ${response.status}`);
    }

    const data = await response.json();
    if (!data.success) {
        throw new Error(data.message || 'Generation failed');
    }

    return {
        url: data.image_url,
        prompt: promptText,
        model: modelId,
        width: width,
        height: height
    };
}

async function generateBatch() {
    if (isGenerating) return;
    
    const promptText = prompt.value.trim();
    const modelId = modelSelect.value;
    const count = parseInt(countSelect.value) || 1;

    if (!validateFields()) {
        showError('Please fill all fields (prompt must be at least 3 characters)');
        return;
    }

    isGenerating = true;
    generateBtn.disabled = true;
    generateBtn.innerHTML = `<span class="spinner"></span> Generating ${count} image${count > 1 ? 's' : ''}...`;
    
    loadingAnimation.style.display = 'block';
    resultContainer.style.display = 'none';
    if (galleryGrid) galleryGrid.innerHTML = '';

    try {
        // Generate images in parallel
        const generationPromises = [];
        for (let i = 0; i < count; i++) {
            generationPromises.push(
                generateSingleImage(promptText, modelId, i)
                    .then(result => ({ success: true, result, index: i }))
                    .catch(error => ({ success: false, error: error.message, index: i }))
            );
        }

        const results = await Promise.all(generationPromises);
        
        const successful = results.filter(r => r.success).map(r => r.result);
        const failed = results.filter(r => !r.success);

        if (successful.length > 0) {
            displayResults(successful);
            showSuccess(`Generated ${successful.length} image${successful.length > 1 ? 's' : ''} successfully!`);
            loadGenerationHistory();
            updateUsageDisplay();
        }

        if (failed.length > 0) {
            showError(`${failed.length} failed: ${failed[0].error}`);
        }

    } catch (err) {
        console.error('Batch generation error:', err);
        showError(err.message || 'Image generation failed');
    } finally {
        isGenerating = false;
        generateBtn.disabled = false;
        generateBtn.innerHTML = `<img src="images/icon-01.png" alt="Generate" class="btn-icon"><span>Generate</span>`;
        loadingAnimation.style.display = 'none';
    }
}

function displayResults(generations) {
    currentGenerations = generations;
    
    console.log('Displaying results:', generations);
    console.log('resultImage:', resultImage);
    console.log('resultContainer:', resultContainer);
    
    // Show single result in main container
    if (generations.length > 0 && resultImage && resultContainer) {
        console.log('Setting main image to:', generations[0].url);
        resultImage.src = generations[0].url;
        resultContainer.style.display = 'block';
    } else {
        console.log('Cannot display main image - missing elements or no generations');
    }
    
    // Show gallery if multiple
    if (generations.length > 1 && galleryGrid) {
        galleryGrid.innerHTML = generations.map((gen, index) => `
            <div class="gallery-item" data-index="${index}">
                <img src="${gen.url}" alt="Generated ${index + 1}" loading="lazy">
                <div class="gallery-overlay">
                    <button class="gallery-btn download-btn" data-index="${index}" title="Download">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </button>
                </div>
            </div>
        `).join('');

        galleryGrid.querySelectorAll('.download-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const index = parseInt(btn.dataset.index);
                downloadImage(currentGenerations[index].url, `genova-${Date.now()}-${index}.png`);
            });
        });
    }
}

function downloadImage(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

async function loadGenerationHistory() {
    try {
        const response = await fetch(API_HISTORY_URL);
        const data = await response.json();
        
        if (data.success && data.generations) {
            displayHistory(data.generations);
        }
    } catch (error) {
        console.error('Failed to load history:', error);
    }
}

function displayHistory(generations) {
    const historyContainer = document.querySelector('.history-grid');
    if (!historyContainer) return;
    
    if (generations.length === 0) {
        historyContainer.innerHTML = '<p class="no-history">No generations yet</p>';
        return;
    }
    
    historyContainer.innerHTML = generations.slice(0, 12).map(gen => `
        <div class="history-item">
            <img src="${gen.image_url}" alt="${gen.prompt}" loading="lazy">
            <div class="history-overlay">
                <p class="history-prompt">${gen.prompt.substring(0, 50)}${gen.prompt.length > 50 ? '...' : ''}</p>
                <p class="history-model">${modelMap[gen.model]?.name || gen.model}</p>
                <p class="history-date">${new Date(gen.created_at).toLocaleDateString()}</p>
            </div>
        </div>
    `).join('');
}

async function updateUsageDisplay() {
    try {
        const response = await fetch(API_HISTORY_URL + '?usage=true');
        const data = await response.json();
        
        if (data.success && data.usage) {
            const usageBar = document.querySelector('.usage-bar-fill');
            const usageText = document.querySelector('.usage-text');
            
            if (usageBar && usageText) {
                const percentage = (data.usage.used / data.usage.limit) * 100;
                usageBar.style.width = `${percentage}%`;
                usageText.textContent = `${data.usage.used} / ${data.usage.limit} images today`;
                
                if (percentage > 80) {
                    usageBar.style.backgroundColor = '#ef4444';
                } else if (percentage > 60) {
                    usageBar.style.backgroundColor = '#f59e0b';
                }
            }
        }
    } catch (error) {
        console.error('Failed to update usage:', error);
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Get all DOM elements
    prompt = document.getElementById('prompt');
    generateBtn = document.getElementById('generate-btn');
    resultImage = document.getElementById('result-image');
    loadingAnimation = document.querySelector('.loading-animation');
    resultContainer = document.querySelector('.result-container');
    modelSelect = document.getElementById('model-select');
    countSelect = document.getElementById('count-select');
    ratioSelect = document.getElementById('ratio-select');
    downloadBtn = document.getElementById('download-btn');
    shareBtn = document.getElementById('share-btn');
    galleryGrid = document.querySelector('.gallery-grid');

    // Add event listeners
    if (generateBtn) {
        generateBtn.addEventListener('click', generateBatch);
    }
    
    if (prompt) {
        prompt.addEventListener('input', validateFields);
    }
    
    if (modelSelect) {
        modelSelect.addEventListener('change', validateFields);
    }
    
    if (ratioSelect) {
        ratioSelect.addEventListener('change', validateFields);
    }
    
    if (downloadBtn) {
        downloadBtn.addEventListener('click', () => {
            if (currentGenerations.length > 0) {
                downloadImage(currentGenerations[0].url, `genova-${Date.now()}.png`);
            }
        });
    }

    // Initialize
    loadGenerationHistory();
    updateUsageDisplay();
    validateFields();
});
