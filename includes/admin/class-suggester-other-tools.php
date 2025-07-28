<?php
/**
 * Suggester Other Tools Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Other Tools Class
 */
class Suggester_Other_Tools {
    
    /**
     * Constructor
     */
    public function __construct() {
        // No specific initialization needed for Other Tools page
    }
    
    /**
     * Render the other tools page
     */
    public function render() {
        ?>
        <div class="suggester-other-tools-container">
            <!-- Main Content -->
            <div class="suggester-other-tools-content">
                <div class="suggester-tools-announcement">
                    <div class="suggester-announcement-icon">
                        <span class="dashicons dashicons-hammer"></span>
                    </div>
                    <div class="suggester-announcement-content">
                        <h2><?php esc_html_e('New Tools on the Way!', 'suggester'); ?></h2>
                        <p><?php esc_html_e('We\'re currently developing an innovative set of tools that will take your experience with us to a whole new level. Stay tuned for updates soon!', 'suggester'); ?></p>
                    </div>
                </div>
                
                <!-- Contact Section -->
                <div class="suggester-contact-section">
                    <div class="suggester-contact-content">
                        <div class="suggester-contact-icon">
                            <span class="dashicons dashicons-email-alt"></span>
                        </div>
                        <div class="suggester-contact-text">
                            <h3><?php esc_html_e('Get in Touch', 'suggester'); ?></h3>
                            <p><?php esc_html_e('Have an idea for a tool, a comment, or would you like to develop a custom tool that fits your needs? We\'d love to hear from you—contact us at', 'suggester'); ?> <a href="mailto:contact@webcava.com" class="suggester-email-link">contact@webcava.com</a></p>
                        </div>
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
            .suggester-other-tools-container {
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .suggester-other-tools-content {
                display: flex;
                flex-direction: column;
                gap: 24px;
            }
            
            .suggester-tools-announcement {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 12px;
                padding: 40px;
                color: #fff;
                text-align: center;
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            }
            
            .suggester-announcement-icon {
                width: 80px;
                height: 80px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px auto;
            }
            
            .suggester-announcement-icon .dashicons {
                font-size: 40px;
                width: 40px;
                height: 40px;
                color: #fff;
            }
            
            .suggester-announcement-content h2 {
                margin: 0 0 16px 0;
                font-size: 28px;
                font-weight: 700;
                color: #fff;
            }
            
            .suggester-announcement-content p {
                margin: 0;
                font-size: 18px;
                line-height: 1.6;
                color: rgba(255, 255, 255, 0.95);
                max-width: 600px;
                margin: 0 auto;
            }
            
            .suggester-contact-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 32px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            
            .suggester-contact-content {
                display: flex;
                align-items: flex-start;
                gap: 24px;
                max-width: 800px;
                margin: 0 auto;
            }
            
            .suggester-contact-icon {
                width: 64px;
                height: 64px;
                background: #2271b1;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            
            .suggester-contact-icon .dashicons {
                font-size: 28px;
                width: 28px;
                height: 28px;
                color: #fff;
            }
            
            .suggester-contact-text {
                flex: 1;
            }
            
            .suggester-contact-text h3 {
                margin: 0 0 12px 0;
                font-size: 20px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .suggester-contact-text p {
                margin: 0;
                font-size: 16px;
                line-height: 1.6;
                color: #50575e;
            }
            
            .suggester-email-link {
                color: #2271b1;
                text-decoration: none;
                font-weight: 600;
                transition: color 0.2s ease;
            }
            
            .suggester-email-link:hover {
                color: #135e96;
                text-decoration: underline;
            }
            
            /* Responsive Design */
            @media (max-width: 768px) {
                .suggester-tools-announcement {
                    padding: 32px 24px;
                }
                
                .suggester-announcement-icon {
                    width: 64px;
                    height: 64px;
                    margin-bottom: 20px;
                }
                
                .suggester-announcement-icon .dashicons {
                    font-size: 32px;
                    width: 32px;
                    height: 32px;
                }
                
                .suggester-announcement-content h2 {
                    font-size: 24px;
                }
                
                .suggester-announcement-content p {
                    font-size: 16px;
                }
                
                .suggester-contact-section {
                    padding: 24px;
                }
                
                .suggester-contact-content {
                    flex-direction: column;
                    text-align: center;
                    gap: 20px;
                }
                
                .suggester-contact-icon {
                    margin: 0 auto;
                }
            }
            
            @media (max-width: 480px) {
                .suggester-tools-announcement {
                    padding: 24px 20px;
                }
                
                .suggester-announcement-content h2 {
                    font-size: 20px;
                }
                
                .suggester-contact-section {
                    padding: 20px;
                }
            }
        </style>
        <?php
    }
} 