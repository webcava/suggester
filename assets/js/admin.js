/**
 * Suggester Admin JavaScript
 *
 * @package Suggester
 * @since 1.0.1
 */

(function() {
    'use strict';

    /**
     * Suggester Admin Object
     */
    var SuggesterAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            document.addEventListener('DOMContentLoaded', function() {
                SuggesterAdmin.bindEvents();
                SuggesterAdmin.initTabs();
            });
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Tab navigation with history management
            var tabs = document.querySelectorAll('.suggester-nav-tab-wrapper .nav-tab');
            
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    var tabKey = this.getAttribute('data-tab');
                    var url = this.getAttribute('href');
                    
                    // Update URL without page reload
                    if (window.history && window.history.pushState) {
                        window.history.pushState({tab: tabKey}, '', url);
                    }
                    
                    // Update active tab
                    SuggesterAdmin.switchTab(tabKey);
                });
            });
            
            // Handle browser back/forward buttons
            window.addEventListener('popstate', function(e) {
                if (e.state && e.state.tab) {
                    SuggesterAdmin.switchTab(e.state.tab);
                }
            });
        },
        
        /**
         * Initialize tab functionality
         */
        initTabs: function() {
            // Get current tab from URL
            var urlParams = new URLSearchParams(window.location.search);
            var currentTab = urlParams.get('tab') || 'overview';
            
            // Ensure the correct tab is active on page load
            this.switchTab(currentTab);
            
            // Store the initial state
            if (window.history && window.history.replaceState) {
                window.history.replaceState({tab: currentTab}, '', window.location.href);
            }
        },
        
        /**
         * Switch to a specific tab
         * 
         * @param {string} tabKey - The tab key to switch to
         */
        switchTab: function(tabKey) {
            // Update tab navigation
            var tabs = document.querySelectorAll('.suggester-nav-tab-wrapper .nav-tab');
            tabs.forEach(function(tab) {
                if (tab.getAttribute('data-tab') === tabKey) {
                    tab.classList.add('nav-tab-active');
                } else {
                    tab.classList.remove('nav-tab-active');
                }
            });
            
            // Hide all tab panels
            var panels = document.querySelectorAll('.suggester-tab-panel');
            panels.forEach(function(panel) {
                panel.style.display = 'none';
            });
            
            // Show the selected panel
            var activePanel = document.getElementById(tabKey + '-panel');
            if (activePanel) {
                activePanel.style.display = 'block';
            }
            
            // Dispatch event for tab change
            var event = new CustomEvent('suggesterTabChanged', {
                detail: { tab: tabKey }
            });
            document.dispatchEvent(event);
        },
        
        /**
         * Show loading state
         * 
         * @param {Element} element - Element to show loading state for
         */
        showLoading: function(element) {
            element.classList.add('suggester-loading');
            if (!element.querySelector('.suggester-spinner')) {
                var spinner = document.createElement('span');
                spinner.className = 'suggester-spinner';
                element.appendChild(spinner);
            }
        },
        
        /**
         * Hide loading state
         * 
         * @param {Element} element - Element to hide loading state for
         */
        hideLoading: function(element) {
            element.classList.remove('suggester-loading');
            var spinner = element.querySelector('.suggester-spinner');
            if (spinner) {
                spinner.parentNode.removeChild(spinner);
            }
        },
        
        /**
         * Make AJAX request with error handling
         * 
         * @param {Object} options - AJAX options
         * @param {Function} successCallback - Success callback
         * @param {Function} errorCallback - Error callback
         */
        ajaxRequest: function(options, successCallback, errorCallback) {
            var xhr = new XMLHttpRequest();
            var url = options.url || suggester_ajax.ajax_url;
            
            xhr.open(options.method || 'POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            if (typeof successCallback === 'function') {
                                successCallback(response.data);
                            }
                        } else {
                            if (typeof errorCallback === 'function') {
                                errorCallback(response.data || 'Unknown error occurred');
                            }
                        }
                    } catch (e) {
                        if (typeof errorCallback === 'function') {
                            errorCallback('Invalid JSON response');
                        }
                    }
                } else {
                    if (typeof errorCallback === 'function') {
                        errorCallback('Request failed with status: ' + xhr.status);
                    }
                }
            };
            
            xhr.onerror = function() {
                if (typeof errorCallback === 'function') {
                    errorCallback('Request failed');
                }
            };
            
            // Prepare data
            var data = options.data || {};
            data.nonce = suggester_ajax.nonce;
            
            var params = Object.keys(data).map(function(key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(data[key]);
            }).join('&');
            
            xhr.send(params);
        },
        
        /**
         * Show notification message
         * 
         * @param {string} message - Message to display
         * @param {string} type - Message type (success, error, warning, info)
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var notice = document.createElement('div');
            notice.className = 'notice notice-' + type + ' is-dismissible';
            notice.innerHTML = '<p>' + message + '</p>';
            
            // Insert after the page title
            var titleEl = document.querySelector('.wrap h1');
            if (titleEl && titleEl.parentNode) {
                titleEl.parentNode.insertBefore(notice, titleEl.nextSibling);
            }
            
            // Auto-remove success notices after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    notice.style.opacity = '0';
                    setTimeout(function() {
                        if (notice.parentNode) {
                            notice.parentNode.removeChild(notice);
                        }
                    }, 300);
                }, 5000);
                
                // Add transition
                notice.style.transition = 'opacity 0.3s';
            }
        }
    };
    
    // Initialize
    SuggesterAdmin.init();
    
    // Make SuggesterAdmin globally available
    window.SuggesterAdmin = SuggesterAdmin;
    
})(); 