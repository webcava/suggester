<?php
/**
 * Suggester Overview Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Overview Class
 */
class Suggester_Overview {
    
    /**
     * Data provider instance
     *
     * @var Suggester_Overview_Data_Provider
     */
    private $data_provider;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->data_provider = new Suggester_Overview_Data_Provider();
    }
    
    /**
     * Render the overview page
     */
    public function render() {
        $today_stats = $this->data_provider->get_today_statistics();
        $most_used_tool = $this->data_provider->get_most_used_tool();
        
        ?>
        <div class="suggester-overview">
            <?php $this->render_today_section($today_stats); ?>
            <?php $this->render_tools_overview($most_used_tool); ?>
            <?php $this->render_quick_links(); ?>
        </div>
        
        <?php $this->render_styles(); ?>
        <?php $this->render_scripts(); ?>
        <?php
    }
    
    /**
     * Render "Today's Result" section
     *
     * @param array $stats Today's statistics
     */
    private function render_today_section($stats) {
        ?>
        <div class="suggester-section today-result">
            <h2><?php esc_html_e('Today\'s Result', 'suggester'); ?></h2>
            
            <!-- First row: Three cards -->
            <div class="stats-row">
                <div class="stat-card favorites">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Today\'s Favorites', 'suggester'); ?></div>
                        <div class="stat-value"><?php echo esc_html($stats['favorites']); ?></div>
                    </div>
                </div>
                
                <div class="stat-card copies">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M16 1H4C2.9 1 2 1.9 2 3v14h2V3h12V1zm3 4H8C6.9 5 6 5.9 6 7v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Today\'s Copies', 'suggester'); ?></div>
                        <div class="stat-value"><?php echo esc_html($stats['copies']); ?></div>
                    </div>
                </div>
                
                <div class="stat-card errors">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Today\'s Errors', 'suggester'); ?></div>
                        <div class="stat-value"><?php echo esc_html($stats['errors']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Second row: Usage card and Statistics link -->
            <div class="stats-row">
                <div class="stat-card usage">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Today\'s Usage', 'suggester'); ?></div>
                        <div class="stat-value"><?php echo esc_html($stats['usage']); ?></div>
                    </div>
                </div>
                
                <div class="detailed-stats-card">
                    <div class="card-content">
                        <h3><?php esc_html_e('Detailed Statistics', 'suggester'); ?></h3>
                        <p><?php esc_html_e('Check out the Statistics tab for more detailed statistics and insights.', 'suggester'); ?></p>
                        <a href="<?php echo esc_url($this->get_tab_url('statistics')); ?>" class="btn-primary">
                            <?php esc_html_e('View Statistics', 'suggester'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render "Tools Overview" section
     *
     * @param array|null $most_used_tool Most used tool data
     */
    private function render_tools_overview($most_used_tool) {
        ?>
        <div class="suggester-section tools-overview">
            <h2><?php esc_html_e('Tools Overview', 'suggester'); ?></h2>
            
            <div class="tools-row">
                <div class="most-used-tools">
                    <h3><?php esc_html_e('Most Used Tool', 'suggester'); ?></h3>
                    <?php if ($most_used_tool) : ?>
                        <div class="tool-table">
                            <table class="wp-list-table widefat">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Tool Name', 'suggester'); ?></th>
                                        <th><?php esc_html_e('Total Uses', 'suggester'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo esc_html($most_used_tool['name']); ?></td>
                                        <td><?php echo esc_html($most_used_tool['uses']); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="tools-footer">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=suggester-tools')); ?>" class="btn-secondary">
                                <?php esc_html_e('See all tools here', 'suggester'); ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="no-tools-message">
                            <p><?php esc_html_e('No tools have been used yet.', 'suggester'); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=suggester-tools')); ?>" class="btn-secondary">
                                <?php esc_html_e('Create your first tool', 'suggester'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="create-tool-card">
                    <div class="card-content">
                        <h3><?php esc_html_e('Create a new tool now', 'suggester'); ?></h3>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=suggester-tools&action=create')); ?>" class="btn-circle">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="currentColor"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render "Quick Links" section
     */
    private function render_quick_links() {
        ?>
        <div class="suggester-section quick-links">
            <h2><?php esc_html_e('Quick Links', 'suggester'); ?></h2>
            
            <!-- First row: Suggestion History -->
            <div class="quick-links-row">
                <div class="quick-link-card suggestion-history">
                    <div class="card-content">
                        <div class="link-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                                <path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="link-content">
                            <h3><?php esc_html_e('Suggestion History', 'suggester'); ?></h3>
                            <p><?php esc_html_e('See keywords used in tools and review previous suggestions.', 'suggester'); ?></p>
                        </div>
                        <div class="link-action">
                            <a href="<?php echo esc_url($this->get_tab_url('history')); ?>" class="btn-primary">
                                <?php esc_html_e('View History', 'suggester'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Second row: General Settings -->
            <div class="quick-links-row">
                <div class="quick-link-card general-settings">
                    <div class="card-content">
                        <div class="link-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                                <path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="link-content">
                            <h3><?php esc_html_e('General Settings', 'suggester'); ?></h3>
                            <p><?php esc_html_e('Configure plugin settings, API keys, and preferences.', 'suggester'); ?></p>
                        </div>
                        <div class="link-action">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=suggester-settings')); ?>" class="btn-primary">
                                <?php esc_html_e('Open Settings', 'suggester'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get tab URL for dashboard navigation
     *
     * @param string $tab_key Tab key
     * @return string Tab URL
     */
    private function get_tab_url($tab_key) {
        return add_query_arg(array(
            'page' => 'suggester',
            'tab' => $tab_key
        ), admin_url('admin.php'));
    }
    
    /**
     * Render CSS styles
     */
    private function render_styles() {
        ?>
        <style>
            .suggester-overview {
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .suggester-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 24px;
                margin-bottom: 32px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            
            .suggester-section h2 {
                margin: 0 0 20px 0;
                font-size: 20px;
                font-weight: 600;
                color: #1d2327;
                border-bottom: 2px solid #f0f0f1;
                padding-bottom: 12px;
            }
            
            /* Today's Result Section */
            .today-result .stats-row {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
                margin-bottom: 20px;
            }
            
            .stat-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 12px;
                padding: 24px;
                color: #fff;
                display: flex;
                align-items: center;
                gap: 16px;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                min-height: 100px;
            }
            
            .stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            }
            
            .stat-card.favorites {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            }
            
            .stat-card.copies {
                background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            }
            
            .stat-card.errors {
                background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            }
            
            .stat-card.usage {
                background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            }
            
            .stat-icon {
                flex-shrink: 0;
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 50%;
            }
            
            .stat-content {
                flex: 1;
            }
            
            .stat-label {
                font-size: 14px;
                font-weight: 500;
                opacity: 0.9;
                margin-bottom: 4px;
            }
            
            .stat-value {
                font-size: 32px;
                font-weight: 700;
                line-height: 1;
            }
            
            .detailed-stats-card {
                background: #f8f9fa;
                border: 2px dashed #c3c4c7;
                border-radius: 12px;
                padding: 24px;
                text-align: center;
                transition: all 0.2s ease;
            }
            
            .detailed-stats-card:hover {
                border-color: #2271b1;
                background: #f6f7f7;
            }
            
            .detailed-stats-card h3 {
                margin: 0 0 8px 0;
                font-size: 18px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .detailed-stats-card p {
                margin: 0 0 16px 0;
                color: #646970;
                font-size: 14px;
            }
            
            /* Tools Overview Section */
            .tools-overview .tools-row {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 24px;
            }
            
            .most-used-tools h3 {
                margin: 0 0 16px 0;
                font-size: 16px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .tool-table {
                margin-bottom: 16px;
            }
            
            .tool-table table {
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            
            .tools-footer {
                text-align: center;
            }
            
            .no-tools-message {
                text-align: center;
                padding: 40px 20px;
                color: #646970;
            }
            
            .no-tools-message p {
                margin: 0 0 16px 0;
                font-style: italic;
            }
            
            .create-tool-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 12px;
                padding: 32px;
                text-align: center;
                color: #fff;
            }
            
            .create-tool-card h3 {
                margin: 0 0 24px 0;
                font-size: 18px;
                font-weight: 600;
            }
            
            .btn-circle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 64px;
                height: 64px;
                background: rgba(255, 255, 255, 0.2);
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                color: #fff;
                text-decoration: none;
                transition: all 0.2s ease;
            }
            
            .btn-circle:hover {
                background: rgba(255, 255, 255, 0.3);
                border-color: rgba(255, 255, 255, 0.5);
                color: #fff;
                transform: scale(1.05);
            }
            
            /* Quick Links Section */
            .quick-links .quick-links-row {
                margin-bottom: 20px;
            }
            
            .quick-link-card {
                background: #f8f9fa;
                border: 1px solid #e1e1e1;
                border-radius: 12px;
                padding: 24px;
                transition: all 0.2s ease;
            }
            
            .quick-link-card:hover {
                border-color: #2271b1;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                transform: translateY(-1px);
            }
            
            .quick-link-card .card-content {
                display: flex;
                align-items: center;
                gap: 20px;
            }
            
            .link-icon {
                flex-shrink: 0;
                width: 64px;
                height: 64px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 12px;
                color: #fff;
            }
            
            .link-content {
                flex: 1;
            }
            
            .link-content h3 {
                margin: 0 0 8px 0;
                font-size: 18px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .link-content p {
                margin: 0;
                color: #646970;
                font-size: 14px;
                line-height: 1.5;
            }
            
            .link-action {
                flex-shrink: 0;
                display: flex;
                align-items: center;
            }
            
            /* Buttons */
            .btn-primary, .btn-secondary {
                display: inline-block;
                padding: 12px 24px;
                border-radius: 6px;
                text-decoration: none;
                font-weight: 500;
                font-size: 14px;
                transition: all 0.2s ease;
                border: none;
                cursor: pointer;
            }
            
            .btn-primary {
                background: #2271b1;
                color: #fff;
            }
            
            .btn-primary:hover {
                background: #135e96;
                color: #fff;
                transform: translateY(-1px);
            }
            
            .btn-secondary {
                background: #f6f7f7;
                color: #50575e;
                border: 1px solid #c3c4c7;
            }
            
            .btn-secondary:hover {
                background: #f0f0f1;
                color: #1d2327;
                border-color: #8c8f94;
            }
            
            /* Responsive Design */
            @media (max-width: 1024px) {
                .tools-overview .tools-row {
                    grid-template-columns: 1fr;
                }
            }
            
            @media (max-width: 768px) {
                .suggester-section {
                    padding: 16px;
                    margin-bottom: 20px;
                }
                
                .today-result .stats-row {
                    grid-template-columns: 1fr;
                    gap: 16px;
                }
                
                .stat-card {
                    padding: 20px;
                    min-height: 80px;
                }
                
                .stat-value {
                    font-size: 24px;
                }
                
                .quick-link-card .card-content {
                    flex-direction: column;
                    text-align: center;
                }
                
                .link-icon {
                    width: 56px;
                    height: 56px;
                }
            }
            
            @media (max-width: 480px) {
                .suggester-overview {
                    margin: 0 -20px;
                }
                
                .suggester-section {
                    border-radius: 0;
                    margin-left: 0;
                    margin-right: 0;
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
                // Add smooth scroll behavior for internal links
                const internalLinks = document.querySelectorAll('a[href^="#"]');
                internalLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const targetId = this.getAttribute('href').substring(1);
                        const targetElement = document.getElementById(targetId);
                        if (targetElement) {
                            targetElement.scrollIntoView({ behavior: 'smooth' });
                        }
                    });
                });
                
                // Add hover effects for stat cards
                const statCards = document.querySelectorAll('.stat-card');
                statCards.forEach(function(card) {
                    card.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-4px)';
                    });
                    
                    card.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                    });
                });
            });
        </script>
        <?php
    }
}

/**
 * Data Provider Class for Overview
 */
class Suggester_Overview_Data_Provider {
    
    /**
     * Get today's statistics
     *
     * @return array Today's statistics
     */
    public function get_today_statistics() {
        $stats = array(
            'favorites' => $this->get_today_count('favorites'),
            'copies' => $this->get_today_count('copies'),
            'errors' => $this->get_today_count('errors'),
            'usage' => $this->get_today_count('usage')
        );
        
        return $stats;
    }
    
    /**
     * Get count for specific metric today
     *
     * @param string $metric Metric name
     * @return int Count
     */
    private function get_today_count($metric) {
        switch ($metric) {
            case 'favorites':
                return $this->get_today_favorites();
                
            case 'copies':
                return $this->get_today_copies();
                
            case 'errors':
                return $this->get_today_errors();
                
            case 'usage':
                return $this->get_today_usage();
                
            default:
                return 0;
        }
    }
    
    /**
     * Get today's favorites count
     *
     * @return int Favorites count
     */
    private function get_today_favorites() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_usage';
        
        // Use calendar day (00:00:00 to 23:59:59) instead of rolling 24 hours
        $today_start = current_time('Y-m-d') . ' 00:00:00';
        $today_end = current_time('Y-m-d') . ' 23:59:59';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0;
        }
        
        // Check if action_type column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'action_type'");
        if (empty($column_exists)) {
            return 0;
        }
        
        // Count favorites for today (calendar day)
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE action_type = 'favorite' AND created_at BETWEEN %s AND %s",
            $today_start,
            $today_end
        )));
    }
    
    /**
     * Get today's copies count
     *
     * @return int Copies count
     */
    private function get_today_copies() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_usage';
        
        // Use calendar day (00:00:00 to 23:59:59) instead of rolling 24 hours
        $today_start = current_time('Y-m-d') . ' 00:00:00';
        $today_end = current_time('Y-m-d') . ' 23:59:59';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0;
        }
        
        // Check if action_type column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'action_type'");
        if (empty($column_exists)) {
            return 0;
        }
        
        // Count copies for today (calendar day)
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE action_type = 'copy' AND created_at BETWEEN %s AND %s",
            $today_start,
            $today_end
        )));
    }
    
    /**
     * Get today's errors count
     *
     * @return int Errors count
     */
    private function get_today_errors() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_errors';
        $today_start = current_time('Y-m-d') . ' 00:00:00';
        $today_end = current_time('Y-m-d') . ' 23:59:59';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0;
        }
        
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE created_at BETWEEN %s AND %s",
            $today_start,
            $today_end
        )));
    }
    
    /**
     * Get today's usage count
     *
     * @return int Usage count
     */
    private function get_today_usage() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_usage';
        
        // Use calendar day (00:00:00 to 23:59:59) instead of rolling 24 hours
        $today_start = current_time('Y-m-d') . ' 00:00:00';
        $today_end = current_time('Y-m-d') . ' 23:59:59';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0;
        }
        
        // Check if action_type column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'action_type'");
        if (empty($column_exists)) {
            // If action_type column doesn't exist, count all entries (backwards compatibility)
            return intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE created_at BETWEEN %s AND %s",
                $today_start,
                $today_end
            )));
        } else {
            // Count only 'usage' action types for today (calendar day)
            return intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE action_type = 'usage' AND created_at BETWEEN %s AND %s",
                $today_start,
                $today_end
            )));
        }
    }
    
    /**
     * Get the most used tool
     *
     * @return array|null Most used tool data or null if no tools exist
     */
    public function get_most_used_tool() {
        $tools = $this->get_all_tools();
        
        if (empty($tools)) {
            return null;
        }
        
        $most_used = null;
        $max_uses = 0;
        
        foreach ($tools as $tool_id => $tool) {
            $tool_stats = get_option('suggester_tool_stats_' . $tool_id, array(
                'uses' => 0,
                'favorites' => 0,
                'copies' => 0,
                'created_date' => current_time('mysql')
            ));
            
            if ($tool_stats['uses'] > $max_uses) {
                $max_uses = $tool_stats['uses'];
                $most_used = array(
                    'id' => $tool_id,
                    'name' => $tool['name'],
                    'uses' => $tool_stats['uses']
                );
            }
        }
        
        return $most_used;
    }
    
    /**
     * Get all tools
     *
     * @return array Array of tools
     */
    private function get_all_tools() {
        $tools = array();
        $tool_counter = get_option('suggester_tool_counter', 0);
        
        // Collect all existing tools
        for ($i = 1; $i <= $tool_counter; $i++) {
            $tool_settings = get_option('suggester_tool_settings_' . $i);
            if ($tool_settings) {
                $tools[$i] = $tool_settings;
            }
        }
        
        return $tools;
    }
} 
