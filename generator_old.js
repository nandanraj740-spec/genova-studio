// Genova Studio - Enhanced Image Generator
// Uses secure PHP proxy with rate limiting and history tracking

const API_BASE_URL = 'api_generate.php';
const API_HISTORY_URL = 'api_get_generations.php';

// Enhanced model map with only free models
const modelMap = {
    // Pollinations - Completely Free, No API Key
    "pollinations/flux": { name: "FLUX (Free)", type: "pollinations", free: true },
    "pollinations/turbo": { name: "Turbo (Fast Free)", type: "pollinations", free: true },
    
    // Craiyon - Completely Free
    "craiyon": { name: "Craiyon (Free)", type: "craiyon", free: true },
    
    // Leonardo AI - Free tier available
    "leonardo/default": { name: "Leonardo AI (Free Tier)", type: "leonardo", free: true }
};

document.addEventListener('DOMContentLoaded', () => {
    const prompt = document.getElementById('prompt');
    const generateBtn = document.getElementById('generate-btn');
    const resultImage = document.getElementById('result-image');
    const loadingAnimation = document.querySelector('.loading-animation');
    const resultContainer = document.querySelector('.result-container');
    const modelSelect = document.getElementById('model-select');
    const countSelect = document.getElementById('count-select');
    const ratioSelect = document.getElementById('ratio-select');
    const downloadBtn = document.getElementById('download-btn');
    const shareBtn = document.getElementById('share-btn');
    const galleryGrid = document.querySelector('.gallery-grid');

    let isGenerating = false;
    let currentGenerations = [];

    // Initialize
    loadGenerationHistory();
    updateUsageDisplay();

    // Add event listener for generate button
    generateBtn.addEventListener('click', generateBatch);


    function showError(message, type = 'error') {
        const errorDiv = document.createElement('div');
        errorDiv.className = `error-message ${type}`;
        errorDiv.textContent = message;
        const promptSection = document.querySelector('.prompt-section');
        promptSection.querySelector('.error-message')?.remove();
        promptSection.appendChild(errorDiv);
        setTimeout(() => errorDiv.remove(), 5000);
    }

    function showSuccess(message) {
        showError(message, 'success');
    }

    function validateFields() {
        const isValid = prompt.value.trim().length >= 3 && modelSelect.value && ratioSelect.value;
        generateBtn.disabled = !isValid || isGenerating;
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
        
        // Show single result in main container
        if (generations.length > 0) {
            resultImage.src = generations[0].url;
            resultContainer.style.display = 'block';
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

    async function loadGenerationHistory() {
        try {
            const response = await fetch(`${API_HISTORY_URL}?page=1&limit=12`);
            if (!response.ok) throw new Error('Failed to load history');
            
            const data = await response.json();
            if (data.success && data.generations) {
                displayHistory(data.generations);
                if (data.usage) updateUsageUI(data.usage);
            }
        } catch (err) {
            console.error('Failed to load history:', err);
        }
    }

    function displayHistory(generations) {
        const historyContainer = document.getElementById('history-grid') || document.querySelector('.history-grid');
        if (!historyContainer) return;

        historyContainer.innerHTML = generations.map(gen => `
            <div class="history-item" data-id="${gen.id}">
                <img src="${gen.image_url}" alt="" loading="lazy">
                <div class="history-info">
                    <p class="history-prompt">${gen.prompt.substring(0, 60)}${gen.prompt.length > 60 ? '...' : ''}</p>
                    <span class="history-model">${modelMap[gen.model]?.name || gen.model}</span>
                    <span class="history-date">${formatDate(gen.created_at)}</span>
                </div>
            </div>
        `).join('');
    }

    function updateUsageUI(usage) {
        const usageDisplay = document.getElementById('usage-display');
        if (usageDisplay) {
            const percentage = (usage.today / usage.limit) * 100;
            usageDisplay.innerHTML = `
                <div class="usage-bar">
                    <div class="usage-progress ${percentage > 80 ? 'warning' : ''}" style="width: ${percentage}%"></div>
                </div>
                <span class="usage-text">${usage.today}/${usage.limit} today (${usage.remaining} remaining)</span>
            `;
        }
    }

    async function updateUsageDisplay() {
        try {
            const response = await fetch(`${API_HISTORY_URL}?page=1&limit=1`);
            if (response.ok) {
                const data = await response.json();
                if (data.usage) updateUsageUI(data.usage);
            }
        } catch (err) {
            console.error('Failed to update usage:', err);
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

    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = (now - date) / 1000;

        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        return `${Math.floor(diff / 86400)}d ago`;
    }

    // Event listeners
    prompt.addEventListener('input', validateFields);
    modelSelect.addEventListener('change', validateFields);
    ratioSelect.addEventListener('change', validateFields);
    generateBtn.addEventListener('click', (e) => { e.preventDefault(); generateBatch(); });

    downloadBtn.addEventListener('click', () => {
        if (resultImage.src && !resultImage.src.includes('data:image')) {
            downloadImage(resultImage.src, `genova-${Date.now()}.png`);
        }
    });

    shareBtn.addEventListener('click', async () => {
        if (resultImage.src && navigator.share) {
            try {
                await navigator.share({
                    title: 'AI Generated Image - Genova Studio',
                    text: 'Check out this AI-generated image!',
                    url: resultImage.src
                });
            } catch (error) {
                if (error.name !== 'AbortError') showError('Sharing failed');
            }
        }
    });

    validateFields();
});
