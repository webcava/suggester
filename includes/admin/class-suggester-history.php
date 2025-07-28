<?php
/**
 * Suggester History Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * History Class
 */
class Suggester_History {
    
    /**
     * Constructor
     */
    public function __construct() {
        // No specific initialization needed for History page
    }
    
    /**
     * Render the history page
     */
    public function render() {
        ?>
        <div class="suggester-history-container">
            <!-- Page Header -->
            <div class="suggester-history-header">
                <h2><?php esc_html_e('Suggestion History — available in the premium version.', 'suggester'); ?></h2>
                <p class="suggester-history-description">
                    <?php esc_html_e('Keep track of all the keywords you\'ve used, the tool\'s suggestions, and which suggestions users liked—all in one place. Upgrade to the premium version to unlock this feature.', 'suggester'); ?>
                </p>
            </div>
            
            <!-- History Table -->
            <div class="suggester-history-table-container">
                <table class="wp-list-table widefat fixed striped suggester-history-table">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column"><?php esc_html_e('Date', 'suggester'); ?></th>
                            <th scope="col" class="manage-column"><?php esc_html_e('Transaction ID', 'suggester'); ?></th>
                            <th scope="col" class="manage-column"><?php esc_html_e('Keyword', 'suggester'); ?></th>
                            <th scope="col" class="manage-column"><?php esc_html_e('Tool', 'suggester'); ?></th>
                            <th scope="col" class="manage-column"><?php esc_html_e('Favorite Suggestions', 'suggester'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="suggester-premium-message">
                                <div class="suggester-premium-content">
                                    <div class="suggester-premium-icon">
                                        <span class="dashicons dashicons-lock"></span>
                                    </div>
                                    <div class="suggester-premium-text">
                                        <?php esc_html_e('Suggestion history cannot be displayed in the free version.', 'suggester'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Premium Upgrade Call-to-Action -->
            <div class="suggester-history-upgrade">
                <div class="suggester-upgrade-content">
                    <h3><?php esc_html_e('Unlock favorites, keyword history, and more with the premium upgrade.', 'suggester'); ?></h3>
                    <div class="suggester-upgrade-features">
                        <div class="suggester-feature-list">
                            <div class="suggester-feature-item">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span><?php esc_html_e('Complete suggestion history tracking', 'suggester'); ?></span>
                            </div>
                            <div class="suggester-feature-item">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span><?php esc_html_e('Keyword analytics and insights', 'suggester'); ?></span>
                            </div>
                            <div class="suggester-feature-item">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span><?php esc_html_e('Favorite suggestions tracking', 'suggester'); ?></span>
                            </div>
                            <div class="suggester-feature-item">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span><?php esc_html_e('Export data to CSV', 'suggester'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="suggester-upgrade-button">
                        <a href="https://www.webcava.com/p/suggestor-pro.html" target="_blank" class="button button-primary button-large suggester-premium-btn">
                            <?php esc_html_e('Upgrade your experience now!', 'suggester'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php $this->render_styles(); ?>
        <?php
    }
    
    /**
     * Render CSS styles
     */
    private function render_styles() {
        ?>
        <style>
            .suggester-history-container {
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .suggester-history-header {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 24px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            
            .suggester-history-header h2 {
                margin: 0 0 16px 0;
                font-size: 24px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .suggester-history-description {
                margin: 0;
                font-size: 16px;
                line-height: 1.6;
                color: #50575e;
            }
            
            .suggester-history-table-container {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
            }
            
            .suggester-history-table {
                margin: 0;
                border: none;
                border-radius: 0;
            }
            
            .suggester-history-table thead th {
                background: #f8f9fa;
                border-bottom: 2px solid #e1e1e1;
                padding: 16px;
                font-weight: 600;
                color: #1d2327;
                text-align: left;
            }
            
            .suggester-premium-message {
                padding: 60px 20px;
                text-align: center;
                background: #f8f9fa;
                border: none;
            }
            
            .suggester-premium-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 16px;
                max-width: 400px;
                margin: 0 auto;
            }
            
            .suggester-premium-icon {
                width: 64px;
                height: 64px;
                background: #2271b1;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
            }
            
            .suggester-premium-icon .dashicons {
                font-size: 32px;
                width: 32px;
                height: 32px;
            }
            
            .suggester-premium-text {
                font-size: 18px;
                font-weight: 500;
                color: #50575e;
                line-height: 1.5;
            }
            
            .suggester-history-upgrade {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 12px;
                padding: 32px;
                color: #fff;
                text-align: center;
            }
            
            .suggester-upgrade-content h3 {
                margin: 0 0 24px 0;
                font-size: 22px;
                font-weight: 600;
                color: #fff;
            }
            
            .suggester-upgrade-features {
                margin: 24px 0 32px 0;
            }
            
            .suggester-feature-list {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 16px;
                max-width: 800px;
                margin: 0 auto;
            }
            
            .suggester-feature-item {
                display: flex;
                align-items: center;
                gap: 12px;
                background: rgba(255, 255, 255, 0.1);
                padding: 16px;
                border-radius: 8px;
                text-align: left;
            }
            
            .suggester-feature-item .dashicons {
                color: #4ade80;
                font-size: 20px;
                width: 20px;
                height: 20px;
                flex-shrink: 0;
            }
            
            .suggester-feature-item span:last-child {
                font-size: 14px;
                font-weight: 500;
                line-height: 1.4;
            }
            
            .suggester-upgrade-button {
                margin-top: 8px;
            }
            
            .suggester-premium-btn {
                background: #fff !important;
                color: #2271b1 !important;
                border: none !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                padding: 12px 32px !important;
                border-radius: 8px !important;
                text-decoration: none !important;
                transition: all 0.2s ease !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            }
            
            .suggester-premium-btn:hover {
                background: #f8f9fa !important;
                color: #135e96 !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
            }
            
            .suggester-premium-btn:focus {
                box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5) !important;
            }
            
            /* Responsive Design */
            @media (max-width: 768px) {
                .suggester-history-header {
                    padding: 20px;
                    margin-bottom: 16px;
                }
                
                .suggester-history-header h2 {
                    font-size: 20px;
                }
                
                .suggester-history-description {
                    font-size: 14px;
                }
                
                .suggester-history-table thead th {
                    padding: 12px 8px;
                    font-size: 13px;
                }
                
                .suggester-premium-message {
                    padding: 40px 16px;
                }
                
                .suggester-premium-text {
                    font-size: 16px;
                }
                
                .suggester-history-upgrade {
                    padding: 24px 20px;
                }
                
                .suggester-upgrade-content h3 {
                    font-size: 18px;
                    margin-bottom: 20px;
                }
                
                .suggester-feature-list {
                    grid-template-columns: 1fr;
                    gap: 12px;
                }
                
                .suggester-feature-item {
                    padding: 12px;
                }
                
                .suggester-premium-btn {
                    font-size: 14px !important;
                    padding: 10px 24px !important;
                }
            }
            
            @media (max-width: 480px) {
                .suggester-history-header {
                    padding: 16px;
                }
                
                .suggester-history-header h2 {
                    font-size: 18px;
                }
                
                .suggester-history-table-container {
                    border-radius: 4px;
                }
                
                .suggester-history-upgrade {
                    border-radius: 8px;
                    padding: 20px 16px;
                }
                
                .suggester-premium-icon {
                    width: 48px;
                    height: 48px;
                }
                
                .suggester-premium-icon .dashicons {
                    font-size: 24px;
                    width: 24px;
                    height: 24px;
                }
            }
        </style>
        <?php
    }
} 