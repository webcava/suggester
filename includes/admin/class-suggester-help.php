<?php
/**
 * Suggester Help Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Help Class
 */
class Suggester_Help {
    
    /**
     * Constructor
     */
    public function __construct() {
        // No specific initialization needed for Help page
    }
    
    /**
     * Render the help page
     */
    public function render() {
        ?>
        <div class="wrap suggester-admin-page">
            <h1><?php esc_html_e('Help', 'suggester'); ?></h1>
            
            <div class="suggester-help-container">
                
                <!-- API Keys Section -->
                <div class="suggester-help-section">
                    <div class="suggester-help-header" data-target="api-keys-content">
                        <h2>
                            <span class="dashicons dashicons-admin-network"></span>
                            <?php esc_html_e('API Keys', 'suggester'); ?>
                        </h2>
                        <span class="suggester-toggle-arrow dashicons dashicons-arrow-down-alt2"></span>
                    </div>
                    
                    <div class="suggester-help-content" id="api-keys-content">
                        <div class="suggester-help-inner">
                            <p><?php esc_html_e('The Suggester plugin relies on API keys from Google Gemini and OpenRouter to generate intelligent content suggestions. These APIs provide the AI-powered functionality that makes the plugin work.', 'suggester'); ?></p>
                            
                            <div class="suggester-api-provider">
                                <h3>
                                    <span class="dashicons dashicons-google"></span>
                                    <?php esc_html_e('Google Gemini API', 'suggester'); ?>
                                </h3>
                                <p><?php esc_html_e('Google Gemini is a powerful AI model that provides fast and accurate content suggestions.', 'suggester'); ?></p>
                                
                                <h4><?php esc_html_e('How to get your Google Gemini API Key:', 'suggester'); ?></h4>
                                <ol>
                                    <li><?php esc_html_e('Visit the Google AI Studio at', 'suggester'); ?> <a href="https://ai.google.dev/" target="_blank" rel="noopener">https://ai.google.dev/</a></li>
                                    <li><?php esc_html_e('Sign in with your Google account', 'suggester'); ?></li>
                                    <li><?php esc_html_e('Click on "Get API Key" in the top navigation', 'suggester'); ?></li>
                                    <li><?php esc_html_e('Create a new API key or use an existing one', 'suggester'); ?></li>
                                    <li><?php esc_html_e('Copy the API key and paste it into the Settings page', 'suggester'); ?></li>
                                    <li><?php esc_html_e('The free tier includes generous usage limits for most websites', 'suggester'); ?></li>
                                </ol>
                            </div>
                            
                            <div class="suggester-api-provider">
                                <h3>
                                    <span class="dashicons dashicons-cloud"></span>
                                    <?php esc_html_e('OpenRouter API', 'suggester'); ?>
                                </h3>
                                <p><?php esc_html_e('OpenRouter provides access to multiple AI models including GPT-4, Claude, and others through a single API.', 'suggester'); ?></p>
                                
                                <h4><?php esc_html_e('How to get your OpenRouter API Key:', 'suggester'); ?></h4>
                                <ol>
                                    <li><?php esc_html_e('Visit OpenRouter at', 'suggester'); ?> <a href="https://openrouter.ai/" target="_blank" rel="noopener">https://openrouter.ai/</a></li>
                                    <li><?php esc_html_e('Create an account or sign in', 'suggester'); ?></li>
                                    <li><?php esc_html_e('Go to the "Keys" section in your dashboard', 'suggester'); ?></li>
                                    <li><?php esc_html_e('Create a new API key', 'suggester'); ?></li>
                                    <li><?php esc_html_e('Add credits to your account (pay-per-use pricing)', 'suggester'); ?></li>
                                    <li><?php esc_html_e('Copy the API key and paste it into the Settings page', 'suggester'); ?></li>
                                    <li><?php esc_html_e('Select your preferred AI model from the dropdown', 'suggester'); ?></li>
                                </ol>
                            </div>
                            
                            <div class="suggester-help-note">
                                <div class="suggester-note-icon">
                                    <span class="dashicons dashicons-info"></span>
                                </div>
                                <div class="suggester-note-content">
                                    <strong><?php esc_html_e('Important:', 'suggester'); ?></strong>
                                    <?php esc_html_e('You need at least one API key to use the plugin. You can use multiple keys for better performance and reliability. The plugin will automatically rotate between available keys.', 'suggester'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Privacy Policy Section -->
                <div class="suggester-help-section">
                    <div class="suggester-help-header" data-target="privacy-policy-content">
                        <h2>
                            <span class="dashicons dashicons-privacy"></span>
                            <?php esc_html_e('Privacy Policy', 'suggester'); ?>
                        </h2>
                        <span class="suggester-toggle-arrow dashicons dashicons-arrow-down-alt2"></span>
                    </div>
                    
                    <div class="suggester-help-content" id="privacy-policy-content">
                        <div class="suggester-help-inner">
                            <h3><?php esc_html_e('Suggester Privacy Policy', 'suggester'); ?></h3>
                            <p><?php esc_html_e('Suggester respects your privacy and the privacy of your website visitors. This page explains how our plugin handles data.', 'suggester'); ?></p>
                            
                            <h4><?php esc_html_e('Data Collection', 'suggester'); ?></h4>
                            <ul>
                                <li><strong><?php esc_html_e('Usage Statistics:', 'suggester'); ?></strong> <?php esc_html_e('The free version only collects anonymous usage statistics (counts and timestamps) to help us improve the plugin.', 'suggester'); ?></li>
                                <li><strong><?php esc_html_e('Search Terms:', 'suggester'); ?></strong> <?php esc_html_e('The free version does NOT store the actual search terms entered by your users in your WordPress database.', 'suggester'); ?></li>
                                <li><strong><?php esc_html_e('User IDs:', 'suggester'); ?></strong> <?php esc_html_e('We store a reference to the WordPress user ID (if logged in) for statistical purposes only.', 'suggester'); ?></li>
                            </ul>
                            
                            <h4><?php esc_html_e('External API Usage', 'suggester'); ?></h4>
                            
                            <div class="suggester-api-privacy-section">
                                <h5><?php esc_html_e('Google Gemini API:', 'suggester'); ?></h5>
                                <ul>
                                    <li><strong><?php esc_html_e('Data Transmission:', 'suggester'); ?></strong> <?php esc_html_e('When a user enters a search term, it is sent to Google\'s Gemini API using your API key.', 'suggester'); ?></li>
                                    <li><strong><?php esc_html_e('Google\'s Data Handling:', 'suggester'); ?></strong> <?php esc_html_e('Google may store and process this data according to their terms of service.', 'suggester'); ?> <a href="https://ai.google.dev/terms" target="_blank" rel="noopener"><?php esc_html_e('View Google AI Terms of Service', 'suggester'); ?></a></li>
                                    <li><strong><?php esc_html_e('Your Responsibility:', 'suggester'); ?></strong> <?php esc_html_e('As the website owner, you are responsible for ensuring your use of the Google Gemini API complies with their terms of service and that you have informed your users about how their data is processed.', 'suggester'); ?></li>
                                </ul>
                            </div>
                            
                            <div class="suggester-api-privacy-section">
                                <h5><?php esc_html_e('OpenRouter API:', 'suggester'); ?></h5>
                                <ul>
                                    <li><strong><?php esc_html_e('Data Transmission:', 'suggester'); ?></strong> <?php esc_html_e('When a user enters a search term, it is sent to OpenRouter\'s API using your API key.', 'suggester'); ?></li>
                                    <li><strong><?php esc_html_e('OpenRouter\'s Data Handling:', 'suggester'); ?></strong> <?php esc_html_e('OpenRouter may store and process this data according to their terms of service.', 'suggester'); ?> <a href="https://openrouter.ai/privacy" target="_blank" rel="noopener"><?php esc_html_e('View OpenRouter Privacy Policy', 'suggester'); ?></a></li>
                                    <li><strong><?php esc_html_e('Your Responsibility:', 'suggester'); ?></strong> <?php esc_html_e('As the website owner, you are responsible for ensuring your use of the OpenRouter API complies with their terms of service and that you have informed your users about how their data is processed.', 'suggester'); ?></li>
                                </ul>
                            </div>
                            
                            <h4><?php esc_html_e('Data Removal', 'suggester'); ?></h4>
                            <ul>
                                <li><?php esc_html_e('All plugin data is automatically removed when you uninstall the plugin.', 'suggester'); ?></li>
                                <li><?php esc_html_e('No data is retained on our servers as all processing happens on your WordPress site and the API providers\' servers.', 'suggester'); ?></li>
                            </ul>
                            
                            <h4><?php esc_html_e('Contact Us', 'suggester'); ?></h4>
                            <p><?php esc_html_e('If you have any questions about this privacy policy or how data is handled in our plugin, please contact us at the email address shown in the plugin information.', 'suggester'); ?></p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <?php $this->render_styles(); ?>
        <?php $this->render_scripts(); ?>
        <?php
    }
    
    /**
     * Render CSS styles
     */
    private function render_styles() {
        ?>
        <style>
            .suggester-help-container {
                max-width: 1000px;
                margin-top: 20px;
            }
            
            .suggester-help-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }
            
            .suggester-help-header {
                padding: 20px;
                background: #f8f9fa;
                border-bottom: 1px solid #e1e1e1;
                cursor: pointer;
                user-select: none;
                display: flex;
                align-items: center;
                justify-content: space-between;
                transition: background-color 0.2s ease;
            }
            
            .suggester-help-header:hover {
                background: #f1f3f4;
            }
            
            .suggester-help-header h2 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                color: #1d2327;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .suggester-help-header .dashicons {
                color: #2271b1;
            }
            
            .suggester-toggle-arrow {
                transition: transform 0.3s ease;
                color: #50575e;
            }
            
            .suggester-help-header.active .suggester-toggle-arrow {
                transform: rotate(180deg);
            }
            
            .suggester-help-content {
                display: none;
                border-top: 1px solid #e1e1e1;
            }
            
            .suggester-help-content.active {
                display: block;
                animation: suggester-slideDown 0.3s ease-out;
            }
            
            @keyframes suggester-slideDown {
                from {
                    opacity: 0;
                    max-height: 0;
                }
                to {
                    opacity: 1;
                    max-height: 1000px;
                }
            }
            
            .suggester-help-inner {
                padding: 24px;
                line-height: 1.6;
            }
            
            .suggester-help-inner h3 {
                margin: 0 0 16px 0;
                font-size: 20px;
                font-weight: 600;
                color: #1d2327;
                border-bottom: 2px solid #2271b1;
                padding-bottom: 8px;
            }
            
            .suggester-help-inner h4 {
                margin: 24px 0 12px 0;
                font-size: 16px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .suggester-help-inner h5 {
                margin: 16px 0 8px 0;
                font-size: 14px;
                font-weight: 600;
                color: #2271b1;
            }
            
            .suggester-help-inner p {
                margin: 0 0 16px 0;
                color: #50575e;
            }
            
            .suggester-help-inner ul,
            .suggester-help-inner ol {
                margin: 0 0 16px 0;
                padding-left: 24px;
            }
            
            .suggester-help-inner li {
                margin-bottom: 8px;
                color: #50575e;
            }
            
            .suggester-help-inner a {
                color: #2271b1;
                text-decoration: none;
            }
            
            .suggester-help-inner a:hover {
                color: #135e96;
                text-decoration: underline;
            }
            
            .suggester-api-provider {
                background: #f8f9fa;
                border: 1px solid #e1e1e1;
                border-radius: 6px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .suggester-api-provider h3 {
                margin: 0 0 12px 0;
                font-size: 16px;
                font-weight: 600;
                color: #1d2327;
                display: flex;
                align-items: center;
                gap: 8px;
                border: none;
                padding: 0;
            }
            
            .suggester-api-provider h3 .dashicons {
                color: #2271b1;
            }
            
            .suggester-api-provider p {
                margin: 0 0 16px 0;
                color: #646970;
                font-size: 14px;
            }
            
            .suggester-api-provider h4 {
                margin: 16px 0 8px 0;
                font-size: 14px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .suggester-api-provider ol {
                margin: 8px 0 0 0;
                padding-left: 20px;
            }
            
            .suggester-api-provider li {
                margin-bottom: 6px;
                font-size: 14px;
                color: #50575e;
            }
            
            .suggester-api-privacy-section {
                background: #f8f9fa;
                border-left: 4px solid #2271b1;
                padding: 16px 20px;
                margin: 16px 0;
            }
            
            .suggester-api-privacy-section h5 {
                margin: 0 0 12px 0;
            }
            
            .suggester-api-privacy-section ul {
                margin: 0;
                padding-left: 20px;
            }
            
            .suggester-api-privacy-section li {
                margin-bottom: 8px;
                font-size: 14px;
            }
            
            .suggester-help-note {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 6px;
                padding: 16px;
                margin: 20px 0;
                display: flex;
                align-items: flex-start;
                gap: 12px;
            }
            
            .suggester-note-icon {
                flex-shrink: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #856404;
            }
            
            .suggester-note-content {
                flex: 1;
                color: #856404;
                font-size: 14px;
                line-height: 1.5;
            }
            
            .suggester-note-content strong {
                font-weight: 600;
            }
            
            /* Responsive Design */
            @media (max-width: 768px) {
                .suggester-help-container {
                    margin: 10px 0;
                }
                
                .suggester-help-header {
                    padding: 16px;
                }
                
                .suggester-help-header h2 {
                    font-size: 16px;
                }
                
                .suggester-help-inner {
                    padding: 20px;
                }
                
                .suggester-help-inner h3 {
                    font-size: 18px;
                }
                
                .suggester-api-provider {
                    padding: 16px;
                }
                
                .suggester-help-note {
                    flex-direction: column;
                    text-align: center;
                    gap: 8px;
                }
            }
            
            @media (max-width: 480px) {
                .suggester-help-section {
                    border-radius: 4px;
                    margin-bottom: 16px;
                }
                
                .suggester-help-header {
                    padding: 12px;
                }
                
                .suggester-help-inner {
                    padding: 16px;
                }
                
                .suggester-api-provider {
                    padding: 12px;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Render JavaScript
     */
    private function render_scripts() {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Collapsible sections functionality
                const headers = document.querySelectorAll('.suggester-help-header');
                
                headers.forEach(function(header) {
                    header.addEventListener('click', function() {
                        const target = this.getAttribute('data-target');
                        const content = document.getElementById(target);
                        const arrow = this.querySelector('.suggester-toggle-arrow');
                        
                        // Toggle active classes
                        this.classList.toggle('active');
                        content.classList.toggle('active');
                        
                        // Update ARIA attributes for accessibility
                        const isExpanded = content.classList.contains('active');
                        this.setAttribute('aria-expanded', isExpanded);
                        content.setAttribute('aria-hidden', !isExpanded);
                    });
                    
                    // Initialize ARIA attributes
                    const target = header.getAttribute('data-target');
                    const content = document.getElementById(target);
                    
                    header.setAttribute('role', 'button');
                    header.setAttribute('aria-expanded', 'false');
                    header.setAttribute('tabindex', '0');
                    content.setAttribute('aria-hidden', 'true');
                    
                    // Add keyboard support
                    header.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.click();
                        }
                    });
                });
                
                // Smooth scrolling for internal links
                const internalLinks = document.querySelectorAll('a[href^="#"]');
                internalLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const targetId = this.getAttribute('href').substring(1);
                        const targetElement = document.getElementById(targetId);
                        if (targetElement) {
                            targetElement.scrollIntoView({ 
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    });
                });
            });
        </script>
        <?php
    }
} 