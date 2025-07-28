/**
 * Vived Template JavaScript
 *
 * @package Suggester
 * @since 1.0.1
 */

(function() {
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all Vived template instances
        const templateContainers = document.querySelectorAll('.suggester-vived');
        
        templateContainers.forEach(function(container) {
            initializeTemplate(container);
        });
    });
    
    /**
     * Initialize a single template instance
     */
    function initializeTemplate(container) {
        // Elements
        const input = container.querySelector('.suggester-input');
        const submitBtn = container.querySelector('.suggester-submit-btn');
        const favoritesHeader = container.querySelector('.suggester-favorites-header');
        const favoritesList = container.querySelector('.suggester-favorites-list');
        const favoritesCount = container.querySelector('.suggester-favorites-count');
        const favoritesToggle = container.querySelector('.suggester-favorites-toggle');
        const emptyFavorites = container.querySelector('.suggester-empty-favorites');
        const resultsSection = container.querySelector('.suggester-results-section');
        const loadingIndicator = container.querySelector('.suggester-loading');
        const resultsList = container.querySelector('.suggester-results-list');
        
        // Templates
        const suggestionTemplate = document.getElementById('suggester-vived-suggestion-template');
        const favoriteTemplate = document.getElementById('suggester-vived-favorite-template');
        
        // Initialize favorites from localStorage
        let favorites = [];
        const storedFavorites = localStorage.getItem('suggester_favorites');
        if (storedFavorites) {
            try {
                favorites = JSON.parse(storedFavorites);
                updateFavoritesUI();
            } catch (e) {
                console.error('Failed to parse stored favorites', e);
                favorites = [];
            }
        }
        
        // Add event listeners
        if (submitBtn) {
            submitBtn.addEventListener('click', handleSubmit);
        }
        
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    handleSubmit();
                }
            });
            
            // Add animation effect to input
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        }
        
        if (favoritesHeader) {
            favoritesHeader.addEventListener('click', toggleFavorites);
        }
        
        /**
         * Handle submit button click
         */
        function handleSubmit() {
            const query = input.value.trim();
            
            if (!query) {
                // Shake input to indicate error
                input.classList.add('error');
                setTimeout(function() {
                    input.classList.remove('error');
                }, 500);
                return;
            }
            
            // Show results section and loading indicator
            resultsSection.style.display = 'block';
            loadingIndicator.style.display = 'flex';
            resultsList.style.display = 'none';
            resultsList.innerHTML = '';
            
            // Scroll to results
            setTimeout(function() {
                resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
            
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
                        loadingIndicator.style.display = 'none';
                        resultsList.style.display = 'block';
                    }
                };
                
                // Call the API
                window.SuggesterApi.generateSuggestions(
                    data,
                    // Success callback
                    function(suggestions) {
                        if (suggestions && suggestions.length > 0) {
                            // Add suggestions to UI
                            suggestions.forEach(function(suggestion) {
                                addSuggestionToUI(suggestion);
                            });
                        } else {
                            // No suggestions returned
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'suggester-error';
                            errorDiv.textContent = 'No suggestions found. Please try a different keyword.';
                            resultsList.appendChild(errorDiv);
                        }
                    },
                    // Error callback
                    function(errorMessage) {
                        // Hide loading indicator
                        loadingIndicator.style.display = 'none';
                        resultsList.style.display = 'block';
                        
                        // Show error
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'suggester-error';
                        errorDiv.textContent = errorMessage || 'Failed to generate suggestions. Please try again.';
                        resultsList.appendChild(errorDiv);
                    }
                );
            } else {
                // SuggesterApi not available
                const errorDiv = document.createElement('div');
                errorDiv.className = 'suggester-error';
                errorDiv.textContent = 'Suggestion API not available. Please refresh the page or contact the administrator.';
                resultsList.appendChild(errorDiv);
                
                // Hide loading indicator
                loadingIndicator.style.display = 'none';
                resultsList.style.display = 'block';
            }
        }
        
        /**
         * Toggle favorites section visibility
         */
        function toggleFavorites() {
            const isVisible = favoritesList.style.display !== 'none';
            
            if (isVisible) {
                favoritesList.style.display = 'none';
                favoritesToggle.classList.remove('open');
            } else {
                favoritesList.style.display = 'block';
                favoritesToggle.classList.add('open');
            }
        }
        
        /**
         * Add a suggestion to the UI
         */
        function addSuggestionToUI(suggestion) {
            if (!suggestionTemplate) return;
            
            const clone = document.importNode(suggestionTemplate.content, true);
            const card = clone.querySelector('.suggester-suggestion-card');
            const content = clone.querySelector('.suggester-suggestion-content');
            const likeBtn = clone.querySelector('.suggester-like-btn');
            const copyBtn = clone.querySelector('.suggester-copy-btn');
            
            // Set suggestion content
            content.textContent = suggestion;
            
            // Add event listeners
            if (likeBtn) {
                // Check if already liked
                const isLiked = favorites.includes(suggestion);
                if (isLiked) {
                    likeBtn.classList.add('liked');
                }
                
                likeBtn.addEventListener('click', function() {
                    toggleFavorite(suggestion, likeBtn);
                });
            }
            
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    copyToClipboard(suggestion, copyBtn);
                });
            }
            
            // Add suggestion to results list
            resultsList.appendChild(card);
            
            // Add hover effect with slight delay for a staggered appearance
            setTimeout(function() {
                card.classList.add('visible');
            }, 100);
        }
        
        /**
         * Toggle a suggestion as favorite
         */
        function toggleFavorite(suggestion, likeBtn) {
            const index = favorites.indexOf(suggestion);
            
            if (index === -1) {
                // Add to favorites
                favorites.push(suggestion);
                likeBtn.classList.add('liked');
                
                // Track the favorite action
                if (window.SuggesterApi && window.SuggesterApi.trackAction) {
                    const toolId = container.dataset.toolId;
                    window.SuggesterApi.trackAction('favorite', toolId);
                }
            } else {
                // Remove from favorites
                favorites.splice(index, 1);
                likeBtn.classList.remove('liked');
            }
            
            // Update localStorage
            localStorage.setItem('suggester_favorites', JSON.stringify(favorites));
            
            // Update favorites UI
            updateFavoritesUI();
        }
        
        /**
         * Update favorites UI
         */
        function updateFavoritesUI() {
            // Clear current favorites
            if (favoritesList) {
                // Keep the empty message div and remove others
                Array.from(favoritesList.children).forEach(child => {
                    if (!child.classList.contains('suggester-empty-favorites')) {
                        child.remove();
                    }
                });
                
                // Show/hide empty message
                if (emptyFavorites) {
                    emptyFavorites.style.display = favorites.length ? 'none' : 'block';
                }
                
                // Add favorites to UI
                favorites.forEach(addFavoriteToUI);
                
                // Update count
                if (favoritesCount) {
                    favoritesCount.textContent = favorites.length;
                }
            }
        }
        
        /**
         * Add a favorite to the UI
         */
        function addFavoriteToUI(suggestion) {
            if (!favoriteTemplate || !favoritesList) return;
            
            const clone = document.importNode(favoriteTemplate.content, true);
            const item = clone.querySelector('.suggester-favorite-item');
            const content = clone.querySelector('.suggester-favorite-content');
            const removeBtn = clone.querySelector('.suggester-remove-favorite');
            
            // Set favorite content
            content.textContent = suggestion;
            
            // Add event listener to remove button
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    // Remove from favorites array
                    const index = favorites.indexOf(suggestion);
                    if (index !== -1) {
                        favorites.splice(index, 1);
                    }
                    
                    // Update localStorage
                    localStorage.setItem('suggester_favorites', JSON.stringify(favorites));
                    
                    // Remove from UI with fade effect
                    item.style.opacity = '0';
                    setTimeout(function() {
                        item.remove();
                        
                        // Show empty message if no favorites
                        if (favorites.length === 0 && emptyFavorites) {
                            emptyFavorites.style.display = 'block';
                        }
                        
                        // Update count
                        if (favoritesCount) {
                            favoritesCount.textContent = favorites.length;
                        }
                    }, 300);
                    
                    // Also update any like buttons in the results
                    const likeButtons = resultsList.querySelectorAll('.suggester-like-btn');
                    likeButtons.forEach(function(btn) {
                        const card = btn.closest('.suggester-suggestion-card');
                        const cardContent = card.querySelector('.suggester-suggestion-content');
                        if (cardContent && cardContent.textContent === suggestion) {
                            btn.classList.remove('liked');
                        }
                    });
                });
            }
            
            // Add to favorites list before the empty message
            favoritesList.appendChild(item);
        }
        
        /**
         * Copy suggestion to clipboard
         */
        function copyToClipboard(text, copyBtn) {
            // Track the copy action
            if (window.SuggesterApi && window.SuggesterApi.trackAction) {
                const toolId = container.dataset.toolId;
                window.SuggesterApi.trackAction('copy', toolId);
            }
            
            navigator.clipboard.writeText(text).then(function() {
                // Show success state
                copyBtn.classList.add('copied');
                
                // Revert after a delay
                setTimeout(function() {
                    copyBtn.classList.remove('copied');
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy text: ', err);
                
                // Fallback method for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';  // Prevent scrolling to bottom
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        copyBtn.classList.add('copied');
                        setTimeout(function() {
                            copyBtn.classList.remove('copied');
                        }, 2000);
                    }
                } catch (err) {
                    console.error('Fallback: Failed to copy text: ', err);
                }
                
                document.body.removeChild(textarea);
            });
        }
    }
})(); 