/**
 * Suggester Frontend Scripts
 *
 * @package Suggester
 * @since 1.0.1
 */

(function() {
    'use strict';
    
    // Initialize Suggester on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        // This script contains global functionality for all templates
        // Template-specific scripts are in the template's script.js file
        
        // Initialize AJAX handlers for API calls
        initApiHandlers();
    });
    
    /**
     * Initialize API handlers for all templates
     */
    function initApiHandlers() {
        // Set up API call handlers that will be used by all templates
        window.SuggesterApi = {
            /**
             * Track user actions (favorites and copies)
             * 
             * @param {string} actionType - Type of action ('favorite' or 'copy')
             * @param {number} toolId - Tool ID
             */
            trackAction: function(actionType, toolId) {
                const formData = new FormData();
                formData.append('action', 'suggester_track_action');
                formData.append('nonce', suggester_data.nonce);
                formData.append('action_type', actionType);
                formData.append('tool_id', toolId || '');
                
                // Send tracking request
                fetch(suggester_data.ajax_url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Action tracked:', actionType, 'for tool', toolId, data.data);
                    } else {
                        console.error('Failed to track action:', data.data);
                    }
                })
                .catch(error => {
                    console.error('Error tracking action:', error);
                });
            },

            /**
             * Generate suggestions using the selected API
             * 
             * @param {object} data - Data for the suggestion generation (keyword, count, language)
             * @param {function} onSuccess - Success callback
             * @param {function} onError - Error callback
             */
            generateSuggestions: function(data, onSuccess, onError) {
                // Get the tool ID from the container
                const toolId = document.querySelector('.suggester-container').dataset.toolId;
                
                // Create form data for the AJAX request
                const formData = new FormData();
                formData.append('action', 'suggester_generate_suggestions');
                formData.append('nonce', suggester_data.nonce);
                formData.append('tool_id', toolId);
                formData.append('keyword', data.keyword || '');
                formData.append('count', data.count || 3);
                formData.append('language', data.language || 'English');
                
                // Display loading state
                if (typeof data.onLoadingStart === 'function') {
                    data.onLoadingStart();
                }
                
                // Make the AJAX request
                fetch(suggester_data.ajax_url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(response => {
                    if (response.success) {
                        if (typeof onSuccess === 'function') {
                            onSuccess(response.data.suggestions);
                        }
                    } else {
                        throw new Error(response.data || 'Failed to generate suggestions');
                    }
                })
                .catch(error => {
                    console.error('Suggestion generation error:', error);
                    if (typeof onError === 'function') {
                        onError(error.message);
                    }
                })
                .finally(() => {
                    // End loading state
                    if (typeof data.onLoadingEnd === 'function') {
                        data.onLoadingEnd();
                    }
                });
            }
        };
    }
})(); 