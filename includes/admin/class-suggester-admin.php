<?php
/**
 * Suggester Admin Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Class
 */
class Suggester_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Ensure Arabic translations are available if needed
        add_action('init', array($this, 'ensure_arabic_translations'), 1);
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_body_class', array($this, 'add_admin_body_class'));
        add_action('admin_head', array($this, 'add_rtl_support'));
    }
    
    /**
     * Ensure Arabic translations are loaded for Arabic/RTL users
     */
    public function ensure_arabic_translations() {
        if (!is_admin()) {
            return;
        }
        
        $locale = get_locale();
        $domain = 'suggester';
        
        // Check if we need Arabic translations
        $lang_param = sanitize_key($_GET['lang'] ?? '');
        $needs_arabic = (
            strpos($locale, 'ar') !== false || 
            is_rtl() || 
            $this->is_rtl_language() ||
            get_option('WPLANG') === 'ar' ||
            (isset($_GET['lang']) && $lang_param === 'ar')
        );
        
        if ($needs_arabic) {
            // Try to load Arabic translation directly
            $mo_file = SUGGESTER_PLUGIN_DIR . 'languages/suggester-ar.mo';
            
            if (file_exists($mo_file)) {
                // Unload any existing translation first
                unload_textdomain($domain);
                
                // Load Arabic translation
                $loaded = load_textdomain($domain, $mo_file);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Suggester: Manually loaded Arabic translation for admin: " . ($loaded ? 'SUCCESS' : 'FAILED'));
                }
                
                // If successful, set a flag that Arabic is loaded
                if ($loaded) {
                    add_option('suggester_arabic_loaded', true, '', 'no');
                }
            }
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page (Dashboard)
        add_menu_page(
            __('Suggester', 'suggester'),
            __('Suggester', 'suggester'),
            'manage_options',
            'suggester',
            array($this, 'dashboard_page'),
            'dashicons-lightbulb',
            30
        );
        
        // Dashboard submenu (same as main page)
        add_submenu_page(
            'suggester',
            __('Dashboard', 'suggester'),
            __('Dashboard', 'suggester'),
            'manage_options',
            'suggester',
            array($this, 'dashboard_page')
        );
        
        // Tools submenu
        add_submenu_page(
            'suggester',
            __('Tools', 'suggester'),
            __('Tools', 'suggester'),
            'manage_options',
            'suggester-tools',
            array($this, 'tools_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'suggester',
            __('Settings', 'suggester'),
            __('Settings', 'suggester'),
            'manage_options',
            'suggester-settings',
            array($this, 'settings_page')
        );
        
        // Help submenu
        add_submenu_page(
            'suggester',
            __('Help', 'suggester'),
            __('Help', 'suggester'),
            'manage_options',
            'suggester-help',
            array($this, 'help_page')
        );
    }
    
    /**
     * Add RTL support for admin pages
     */
    public function add_rtl_support() {
        if (!$this->is_suggester_admin_page()) {
            return;
        }
        
        if ($this->is_rtl_language()) {
            echo '<script>document.documentElement.setAttribute("dir", "rtl");</script>';
        }
    }
    
    /**
     * Add body class for admin pages
     */
    public function add_admin_body_class($classes) {
        if (!$this->is_suggester_admin_page()) {
            return $classes;
        }
        
        $classes .= ' suggester-admin-page';
        
        if ($this->is_rtl_language()) {
            $classes .= ' rtl';
        }
        
        return $classes;
    }
    
    /**
     * Check if current page is a Suggester admin page
     */
    private function is_suggester_admin_page() {
        $screen = get_current_screen();
        return $screen && strpos($screen->id, 'suggester') !== false;
    }
    
    /**
     * Check if current language is RTL
     */
    private function is_rtl_language() {
        $locale = get_locale();
        $rtl_locales = array(
            'ar',    // Arabic
            'arc',   // Aramaic
            'dv',    // Divehi
            'fa',    // Persian/Farsi
            'he',    // Hebrew
            'ku',    // Kurdish
            'ps',    // Pashto
            'sd',    // Sindhi
            'ug',    // Uighur
            'ur',    // Urdu
            'yi'     // Yiddish
        );
        
        // Check if the locale starts with any RTL language code
        foreach ($rtl_locales as $rtl_locale) {
            if (strpos($locale, $rtl_locale) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on Suggester admin pages
        if (strpos($hook, 'suggester') === false) {
            return;
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'suggester-admin',
            SUGGESTER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SUGGESTER_VERSION
        );
        
        // Enqueue WordPress color picker
        if (strpos($hook, 'suggester-tools') !== false) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
        
        // Enqueue admin JS
        wp_enqueue_script(
            'suggester-admin',
            SUGGESTER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            SUGGESTER_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('suggester-admin', 'suggester_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('suggester_nonce'),
        ));
    }
    
    /**
     * Dashboard page callback
     */
    public function dashboard_page() {
        $dashboard = new Suggester_Dashboard();
        $dashboard->render();
    }
    
    /**
     * Tools page callback
     */
    public function tools_page() {
        $tools = new Suggester_Tools();
        $tools->render();
    }
    
    /**
     * Settings page callback
     */
    public function settings_page() {
        $this->render_headers();
        $settings = new Suggester_Settings();
        $settings->render();
    }
    
    /**
     * Help page callback
     */
    public function help_page() {
        $this->render_headers();
        $help = new Suggester_Help();
        $help->render();
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