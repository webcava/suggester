<?php
/**
 * Suggester Dashboard Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Class
 */
class Suggester_Dashboard {
    
    /**
     * Current active tab
     *
     * @var string
     */
    private $current_tab;
    
    /**
     * Available tabs
     *
     * @var array
     */
    private $tabs;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->current_tab = $this->get_current_tab();
        $this->tabs = array(
            'overview' => __('Overview', 'suggester'),
            'statistics' => __('Statistics', 'suggester'),
            'history' => __('Suggestion History', 'suggester'),
            'other-tools' => __('Our Other Tools', 'suggester'),
        );
    }
    
    /**
     * Get current active tab
     *
     * @return string
     */
    private function get_current_tab() {
        return isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
    }
    
    /**
     * Render the dashboard page
     */
    public function render() {
        $this->render_headers();
        ?>
        <div class="wrap suggester-admin-page">
            <h1><?php esc_html_e('Suggester Dashboard', 'suggester'); ?></h1>
            
            <?php $this->render_tabs(); ?>
            
            <div class="suggester-tab-content">
                <?php $this->render_tab_content(); ?>
            </div>

            <script>
                // Load the initial tab from URL or sessionStorage
                document.addEventListener('DOMContentLoaded', function() {
                    var urlParams = new URLSearchParams(window.location.search);
                    var tabFromUrl = urlParams.get('tab');
                    
                    // Use tab from URL if available (maintains tab on page reload)
                    if (tabFromUrl) {
                        // Save this as the current tab in sessionStorage
                        sessionStorage.setItem('suggester_dashboard_reload_tab', tabFromUrl);
                        
                        // Apply tab switching if necessary
                        if (window.SuggesterAdmin) {
                            window.SuggesterAdmin.switchTab(tabFromUrl);
                        }
                    } else {
                        // Check if we have a reload tab saved (for browser reload only)
                        var reloadTab = sessionStorage.getItem('suggester_dashboard_reload_tab');
                        
                        // Only use the reload tab if this appears to be a browser reload/refresh
                        // We can detect this by checking document.referrer
                        var isPageReload = document.referrer === window.location.href || 
                                         (document.referrer.indexOf('page=suggester') !== -1 && 
                                          !document.referrer.match(/page=suggester-(?:tools|settings)/));
                        
                        if (isPageReload && reloadTab) {
                            // This is a page reload, use the saved tab
                            var newUrl = new URL(window.location.href);
                            newUrl.searchParams.set('tab', reloadTab);
                            
                            // Update URL without reloading
                            if (window.history && window.history.replaceState) {
                                window.history.replaceState({tab: reloadTab}, '', newUrl.toString());
                            }
                            
                            // Switch to the tab
                            if (window.SuggesterAdmin) {
                                window.SuggesterAdmin.switchTab(reloadTab);
                            }
                        } else {
                            // This is a new navigation to the page, use default tab
                            sessionStorage.setItem('suggester_dashboard_reload_tab', 'overview');
                            
                            // Switch to default tab if needed
                            if (window.SuggesterAdmin) {
                                window.SuggesterAdmin.switchTab('overview');
                            }
                        }
                    }
                });
                
                // Listen for tab changes and update the reload tab in session storage
                document.addEventListener('suggesterTabChanged', function(e) {
                    if (e.detail && e.detail.tab) {
                        sessionStorage.setItem('suggester_dashboard_reload_tab', e.detail.tab);
                    }
                });
            </script>
        </div>
        <?php
    }
    
    /**
     * Render navigation tabs
     */
    private function render_tabs() {
        ?>
        <nav class="nav-tab-wrapper suggester-nav-tab-wrapper">
            <?php foreach ($this->tabs as $tab_key => $tab_name) : ?>
                <a href="<?php echo esc_url($this->get_tab_url($tab_key)); ?>" 
                   class="nav-tab <?php echo ($this->current_tab === $tab_key) ? 'nav-tab-active' : ''; ?>"
                   data-tab="<?php echo esc_attr($tab_key); ?>">
                    <?php echo esc_html($tab_name); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }
    
    /**
     * Get tab URL
     *
     * @param string $tab_key
     * @return string
     */
    private function get_tab_url($tab_key) {
        return add_query_arg(array(
            'page' => 'suggester',
            'tab' => $tab_key
        ), admin_url('admin.php'));
    }
    
    /**
     * Render tab content
     */
    private function render_tab_content() {
        // Render all tabs, but only the current one will be visible
        // This avoids having to reload the page when switching tabs
        ?>
        <div class="suggester-tab-panel" id="overview-panel" style="<?php echo esc_attr($this->current_tab === 'overview' ? 'display:block;' : 'display:none;'); ?>">
            <?php $this->render_overview_tab(); ?>
        </div>
        
        <div class="suggester-tab-panel" id="statistics-panel" style="<?php echo esc_attr($this->current_tab === 'statistics' ? 'display:block;' : 'display:none;'); ?>">
            <?php 
            $statistics = new Suggester_Statistics();
            $statistics->render_statistics();
            ?>
        </div>
        
        <div class="suggester-tab-panel" id="history-panel" style="<?php echo esc_attr($this->current_tab === 'history' ? 'display:block;' : 'display:none;'); ?>">
            <?php 
            $history = new Suggester_History();
            $history->render();
            ?>
        </div>
        
        <div class="suggester-tab-panel" id="other-tools-panel" style="<?php echo esc_attr($this->current_tab === 'other-tools' ? 'display:block;' : 'display:none;'); ?>">
            <?php 
            $other_tools = new Suggester_Other_Tools();
            $other_tools->render();
            ?>
        </div>
        <?php
    }
    
    /**
     * Render overview tab content
     */
    private function render_overview_tab() {
        $overview = new Suggester_Overview();
        $overview->render();
    }
    
    /**
     * Render plugin headers (main and secondary)
     */
    private function render_headers() {
        ?>
        <div class="suggester-plugin-headers">
            <!-- Main Header -->
            <div class="suggester-main-header">
                <div class="suggester-main-header-content">
                    <div class="suggester-logo">
                        <span class="suggester-logo-text">Suggester</span>
                    </div>
                    <div class="suggester-version-badge">
                        <span class="suggester-version-text"><?php esc_html_e('You\'re using the free version.', 'suggester'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Secondary Header -->
            <div class="suggester-secondary-header">
                <div class="suggester-secondary-header-content">
                    <span class="suggester-contact-message">
                        <?php esc_html_e('Facing issues or have a suggestion? Contact us at:', 'suggester'); ?>
                        <a href="mailto:contact@webcava.com" class="suggester-contact-link">contact@webcava.com</a>
                    </span>
                </div>
            </div>
        </div>
        
        <?php $this->render_header_styles(); ?>
        <?php
    }
    
    /**
     * Render header CSS styles
     */
    private function render_header_styles() {
        ?>
        <style>
            .suggester-plugin-headers {
                margin: 0 0 20px 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            
            /* Main Header */
            .suggester-main-header {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 16px 24px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                margin-bottom: 8px; /* 8px gap between headers */
            }
            
            .suggester-main-header-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .suggester-logo {
                display: flex;
                align-items: center;
            }
            
            .suggester-logo-text {
                font-size: 24px;
                font-weight: 700;
                color: #1d2327;
                text-decoration: none;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                letter-spacing: -0.5px;
            }
            
            .suggester-version-badge {
                display: flex;
                align-items: center;
            }
            
            .suggester-version-text {
                background: #f0f6fc;
                color: #0969da;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                border: 1px solid #d1d9e0;
            }
            
            /* Secondary Header */
            .suggester-secondary-header {
                background: #f8f9fa;
                border: 1px solid #e1e1e1;
                border-radius: 6px;
                padding: 12px 24px;
                margin-bottom: 0;
            }
            
            .suggester-secondary-header-content {
                max-width: 1200px;
                margin: 0 auto;
                text-align: center;
            }
            
            .suggester-contact-message {
                font-size: 14px;
                color: #646970;
                line-height: 1.4;
            }
            
            .suggester-contact-link {
                color: #2271b1;
                text-decoration: none;
                font-weight: 600;
                margin-left: 4px;
                transition: color 0.2s ease;
            }
            
            .suggester-contact-link:hover {
                color: #135e96;
                text-decoration: underline;
            }
            
            .suggester-contact-link:focus {
                outline: 2px solid #2271b1;
                outline-offset: 2px;
                border-radius: 2px;
            }
            
            /* Responsive Design */
            @media (max-width: 768px) {
                .suggester-main-header {
                    padding: 12px 16px;
                }
                
                .suggester-main-header-content {
                    flex-direction: column;
                    gap: 12px;
                    text-align: center;
                }
                
                .suggester-logo-text {
                    font-size: 20px;
                }
                
                .suggester-version-text {
                    font-size: 13px;
                    padding: 6px 12px;
                }
                
                .suggester-secondary-header {
                    padding: 10px 16px;
                }
                
                .suggester-contact-message {
                    font-size: 13px;
                }
            }
            
            @media (max-width: 480px) {
                .suggester-main-header {
                    padding: 10px 12px;
                    margin-bottom: 6px;
                }
                
                .suggester-main-header-content {
                    gap: 10px;
                }
                
                .suggester-logo-text {
                    font-size: 18px;
                }
                
                .suggester-version-text {
                    font-size: 12px;
                    padding: 5px 10px;
                }
                
                .suggester-secondary-header {
                    padding: 8px 12px;
                }
                
                .suggester-contact-message {
                    font-size: 12px;
                    line-height: 1.5;
                }
            }
        </style>
        <?php
    }
} 