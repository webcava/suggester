/**
 * Night Mode Template JavaScript
 *
 * @package Suggester
 * @since 1.0.1
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize each instance of the suggester
        const suggesterContainers = document.querySelectorAll('.suggester-night-mode');
        
        suggesterContainers.forEach(function(container) {
            initSuggester(container);
        });
    });
    
    /**
     * Initialize a single suggester instance
     */
    function initSuggester(container) {
        // Elements
        const inputEl = container.querySelector('.suggester-input');
        const submitBtn = container.querySelector('.suggester-submit-btn');
        const favoritesHeader = container.querySelector('.suggester-favorites-header');
        const favoritesList = container.querySelector('.suggester-favorites-list');
        const favoritesToggle = container.querySelector('.suggester-favorites-toggle');
        const resultsSection = container.querySelector('.suggester-results-section');
        const loadingEl = container.querySelector('.suggester-loading');
        const resultsList = container.querySelector('.suggester-results-list');
        const favoritesCount = container.querySelector('.suggester-favorites-count');
        
        // Check if favorites feature is enabled
        const hasFavorites = !!favoritesHeader;
        
        // Templates
        const suggestionTemplate = document.getElementById('suggester-suggestion-template');
        const favoriteTemplate = document.getElementById('suggester-favorite-template');
        
        // Local storage key for favorites
        const storageKey = 'suggester_favorites_' + getContainerId(container);
        
        // Initialize favorites from local storage
        if (hasFavorites) {
            initFavorites();
        }
        
        // Event listeners
        if (submitBtn) {
            submitBtn.addEventListener('click', handleSubmit);
        }
        
        if (inputEl) {
            inputEl.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    handleSubmit();
                }
            });
        }
        
        if (hasFavorites && favoritesHeader) {
            favoritesHeader.addEventListener('click', toggleFavorites);
        }
        
        /**
         * Toggle favorites visibility
         */
        function toggleFavorites() {
            const isOpen = favoritesList.style.display !== 'none';
            
            if (isOpen) {
                favoritesList.style.display = 'none';
                favoritesToggle.classList.remove('open');
            } else {
                favoritesList.style.display = 'block';
                favoritesToggle.classList.add('open');
            }
        }
        
        /**
         * Handle form submission
         */
        function handleSubmit() {
            const query = inputEl.value.trim();
            
            if (!query) {
                // Show error or focus input
                inputEl.focus();
                return;
            }
            
            // Show results section and loading indicator
            resultsSection.style.display = 'block';
            loadingEl.style.display = 'flex';
            resultsList.style.display = 'none';
            resultsList.innerHTML = '';
            
            // Call the API to generate suggestions
            if (window.SuggesterApi) {
                // Prepare data for API call
                const data = {
                    keyword: query,
                    count: 3, // Default count, can be customized in settings
                    language: 'English', // Default language, can be customized
                    onLoadingStart: function() {
                        // Loading state is already set above
                    },
                    onLoadingEnd: function() {
                        // Hide loading indicator
                        loadingEl.style.display = 'none';
                        resultsList.style.display = 'block';
                    }
                };
                
                // Call the API
                window.SuggesterApi.generateSuggestions(
                    data,
                    // Success callback
                    function(suggestions) {
                        if (suggestions && suggestions.length > 0) {
                            // Render suggestions
                            suggestions.forEach(function(text) {
                                appendSuggestion(text);
                            });
                        } else {
                            // No suggestions returned
                            const noResultsEl = document.createElement('p');
                            noResultsEl.className = 'suggester-no-results';
                            noResultsEl.textContent = 'No suggestions found. Please try a different keyword.';
                            resultsList.appendChild(noResultsEl);
                        }
                    },
                    // Error callback
                    function(errorMessage) {
                        // Hide loading indicator
                        loadingEl.style.display = 'none';
                        resultsList.style.display = 'block';
                        
                        // Show error message
                        const errorEl = document.createElement('p');
                        errorEl.className = 'suggester-error';
                        errorEl.textContent = errorMessage || 'Failed to generate suggestions. Please try again.';
                        resultsList.appendChild(errorEl);
                    }
                );
            } else {
                // SuggesterApi not available
                const errorEl = document.createElement('p');
                errorEl.className = 'suggester-error';
                errorEl.textContent = 'Suggestion API not available. Please refresh the page or contact the administrator.';
                resultsList.appendChild(errorEl);
                
                // Hide loading indicator
                loadingEl.style.display = 'none';
                resultsList.style.display = 'block';
            }
        }
        
        /**
         * Append a suggestion to the results list
         */
        function appendSuggestion(text) {
            if (!suggestionTemplate) return;
            
            // Clone the template
            const suggestionEl = document.importNode(suggestionTemplate.content, true).firstElementChild;
            
            // Set the content
            const contentEl = suggestionEl.querySelector('.suggester-suggestion-content');
            contentEl.textContent = text;
            
            // Add event listeners
            const likeBtn = suggestionEl.querySelector('.suggester-like-btn');
            const copyBtn = suggestionEl.querySelector('.suggester-copy-btn');
            
            if (likeBtn) {
                likeBtn.addEventListener('click', function() {
                    this.classList.toggle('liked');
                    
                    if (this.classList.contains('liked')) {
                        // Add to favorites
                        addToFavorites(text);
                        
                        // Track the favorite action
                        if (window.SuggesterApi && window.SuggesterApi.trackAction) {
                            const toolId = container.dataset.toolId;
                            window.SuggesterApi.trackAction('favorite', toolId);
                        }
                    } else {
                        // Remove from favorites
                        removeFromFavorites(text);
                    }
                });
                
                // Check if this suggestion is already in favorites
                if (hasFavorites && isFavorite(text)) {
                    likeBtn.classList.add('liked');
                }
            }
            
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    copyToClipboard(text);
                    
                    // Track the copy action
                    if (window.SuggesterApi && window.SuggesterApi.trackAction) {
                        const toolId = container.dataset.toolId;
                        window.SuggesterApi.trackAction('copy', toolId);
                    }
                    
                    // Show copied state
                    this.classList.add('copied');
                    
                    // Hide copy icon, show check icon
                    const copyIcon = this.querySelector('.copy-icon');
                    const checkIcon = this.querySelector('.check-icon');
                    
                    if (copyIcon) copyIcon.style.display = 'none';
                    if (checkIcon) checkIcon.style.display = 'block';
                    
                    // Reset after delay
                    setTimeout(() => {
                        this.classList.remove('copied');
                        if (copyIcon) copyIcon.style.display = 'block';
                        if (checkIcon) checkIcon.style.display = 'none';
                    }, 1500);
                });
            }
            
            // Add to results list
            resultsList.appendChild(suggestionEl);
        }
        
        /**
         * Initialize favorites from local storage
         */
        function initFavorites() {
            const favorites = getFavorites();
            updateFavoritesCount(favorites.length);
            
            // Clear favorites list except the empty message
            const emptyMessage = favoritesList.querySelector('.suggester-empty-favorites');
            favoritesList.innerHTML = '';
            favoritesList.appendChild(emptyMessage);
            
            // If we have favorites, hide the empty message and render favorites
            if (favorites.length > 0) {
                emptyMessage.style.display = 'none';
                
                favorites.forEach(function(text) {
                    appendFavorite(text);
                });
            } else {
                emptyMessage.style.display = 'block';
            }
        }
        
        /**
         * Get favorites from local storage
         */
        function getFavorites() {
            try {
                const data = localStorage.getItem(storageKey);
                return data ? JSON.parse(data) : [];
            } catch (e) {
                console.error('Error loading favorites', e);
                return [];
            }
        }
        
        /**
         * Save favorites to local storage
         */
        function saveFavorites(favorites) {
            try {
                localStorage.setItem(storageKey, JSON.stringify(favorites));
            } catch (e) {
                console.error('Error saving favorites', e);
            }
        }
        
        /**
         * Check if text is in favorites
         */
        function isFavorite(text) {
            const favorites = getFavorites();
            return favorites.includes(text);
        }
        
        /**
         * Add text to favorites
         */
        function addToFavorites(text) {
            if (!hasFavorites) return;
            
            const favorites = getFavorites();
            
            // Avoid duplicates
            if (!favorites.includes(text)) {
                favorites.push(text);
                saveFavorites(favorites);
                appendFavorite(text);
                updateFavoritesCount(favorites.length);
                
                // Hide empty message if shown
                const emptyMessage = favoritesList.querySelector('.suggester-empty-favorites');
                if (emptyMessage) {
                    emptyMessage.style.display = 'none';
                }
            }
        }
        
        /**
         * Remove text from favorites
         */
        function removeFromFavorites(text) {
            if (!hasFavorites) return;
            
            const favorites = getFavorites();
            const index = favorites.indexOf(text);
            
            if (index !== -1) {
                favorites.splice(index, 1);
                saveFavorites(favorites);
                
                // Update the UI
                const items = favoritesList.querySelectorAll('.suggester-favorite-item');
                for (let i = 0; i < items.length; i++) {
                    const content = items[i].querySelector('.suggester-favorite-content');
                    if (content && content.textContent === text) {
                        items[i].remove();
                        break;
                    }
                }
                
                updateFavoritesCount(favorites.length);
                
                // Show empty message if no favorites left
                if (favorites.length === 0) {
                    const emptyMessage = favoritesList.querySelector('.suggester-empty-favorites');
                    if (emptyMessage) {
                        emptyMessage.style.display = 'block';
                    } else {
                        // Re-create empty message if it doesn't exist
                        const newEmptyMessage = document.createElement('div');
                        newEmptyMessage.className = 'suggester-empty-favorites';
                        newEmptyMessage.textContent = 'No saved suggestions yet.';
                        favoritesList.appendChild(newEmptyMessage);
                    }
                }
                
                // Update like buttons in suggestion cards
                const likeButtons = resultsList.querySelectorAll('.suggester-like-btn');
                likeButtons.forEach(function(button) {
                    const card = button.closest('.suggester-suggestion-card');
                    const content = card.querySelector('.suggester-suggestion-content');
                    
                    if (content && content.textContent === text) {
                        button.classList.remove('liked');
                    }
                });
            }
        }
        
        /**
         * Append favorite to the favorites list
         */
        function appendFavorite(text) {
            if (!favoriteTemplate || !hasFavorites) return;
            
            // Clone the template
            const favoriteEl = document.importNode(favoriteTemplate.content, true).firstElementChild;
            
            // Set the content
            const contentEl = favoriteEl.querySelector('.suggester-favorite-content');
            contentEl.textContent = text;
            
            // Add event listener to remove button
            const removeBtn = favoriteEl.querySelector('.suggester-remove-favorite');
            if (removeBtn) {
                removeBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    removeFromFavorites(text);
                });
            }
            
            // Add to favorites list
            favoritesList.appendChild(favoriteEl);
        }
        
        /**
         * Update favorites count display
         */
        function updateFavoritesCount(count) {
            if (favoritesCount) {
                favoritesCount.textContent = count;
            }
        }
        
        /**
         * Copy text to clipboard
         */
        function copyToClipboard(text) {
            // Try the modern navigator.clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).catch(function(err) {
                    console.error('Failed to copy text: ', err);
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        }
        
        /**
         * Fallback method for copying to clipboard
         */
        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
            } catch (err) {
                console.error('Fallback copy method failed:', err);
            }
            
            document.body.removeChild(textArea);
        }
        
        /**
         * Get a unique ID for the container to use in local storage
         */
        function getContainerId(container) {
            // Try to get from data attribute
            let id = container.getAttribute('data-tool-id');
            
            // If no ID, generate one based on DOM position
            if (!id) {
                const containers = document.querySelectorAll('.suggester-night-mode');
                for (let i = 0; i < containers.length; i++) {
                    if (containers[i] === container) {
                        id = 'night-mode-' + i;
                        break;
                    }
                }
            }
            
            return id || 'default';
        }
    }
})(); 