<?php
/**
 * Suggester Shortcode Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode Class
 */
class Suggester_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('suggester', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('wp_head', array($this, 'add_frontend_rtl_support'));
        
        // Ensure textdomain is loaded for frontend
        add_action('init', array($this, 'ensure_frontend_translations'), 5);
    }
    
    /**
     * Add RTL support to frontend
     */
    public function add_frontend_rtl_support() {
        if ($this->is_rtl_language()) {
            echo '<script>if(document.documentElement){document.documentElement.classList.add("rtl");}</script>';
        }
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
     * Register frontend assets
     */
    public function register_assets() {
        // Register frontend CSS
        wp_register_style(
            'suggester-frontend',
            SUGGESTER_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SUGGESTER_VERSION
        );
        
        // Register frontend JS
        wp_register_script(
            'suggester-frontend',
            SUGGESTER_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            SUGGESTER_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('suggester-frontend', 'suggester_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('suggester_frontend_nonce'),
            'is_rtl' => $this->is_rtl_language(),
        ));
    }
    
    /**
     * Render shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function render_shortcode($atts) {
        // Enqueue assets
        wp_enqueue_style('suggester-frontend');
        wp_enqueue_script('suggester-frontend');
        
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'suggester');
        
        // Get tool ID
        $tool_id = absint($atts['id']);
        
        // Check if tool exists
        if ($tool_id <= 0) {
            return '<p class="suggester-error">' . esc_html__('Invalid suggestion tool ID.', 'suggester') . '</p>';
        }
        
        // Get tool settings
        $tool_settings = get_option('suggester_tool_settings_' . $tool_id);
        
        // Check if tool exists and is active
        if (!$tool_settings) {
            return '<p class="suggester-error">' . esc_html__('Suggestion tool not found.', 'suggester') . '</p>';
        }
        
        if (empty($tool_settings['active'])) {
            return '<p class="suggester-error">' . esc_html__('This suggestion tool is currently disabled.', 'suggester') . '</p>';
        }
        
        // Get template
        $template = isset($tool_settings['template']) ? $tool_settings['template'] : 'night-mode';
        
        // Include template functions
        require_once SUGGESTER_PLUGIN_DIR . 'assets/templates/index.php';
        
        // Enqueue template assets
        suggester_enqueue_template_assets($template);
        
        // Render template
        $output = suggester_render_template($template, $tool_settings);
        
        // Add data attributes for tool ID and RTL support
        $rtl_class = $this->is_rtl_language() ? ' suggester-rtl' : '';
        $rtl_attr = $this->is_rtl_language() ? ' dir="rtl"' : '';
        
        $output = str_replace(
            'class="suggester-container', 
            'data-tool-id="' . esc_attr($tool_id) . '"' . $rtl_attr . ' class="suggester-container' . $rtl_class, 
            $output
        );
        
        return $output;
    }
    
    /**
     * Ensure translations are loaded for frontend
     */
    public function ensure_frontend_translations() {
        // Force load textdomain if not already loaded
        $domain = 'suggester';
        
        // Check if we're in frontend and translations might be needed
        if (!is_admin()) {
            global $l10n;
            
            // If textdomain is not loaded, load it
            if (!isset($l10n[$domain])) {
                $locale = get_locale();
                $plugin_rel_path = dirname(plugin_basename(SUGGESTER_PLUGIN_FILE)) . '/languages';
                
                // Try loading translation
                $loaded = load_plugin_textdomain($domain, false, $plugin_rel_path);
                
                // If not loaded and we need Arabic, try direct loading
                if (!$loaded && (strpos($locale, 'ar') !== false || $this->is_rtl_language())) {
                    $mo_file = SUGGESTER_PLUGIN_DIR . 'languages/suggester-ar.mo';
                    if (file_exists($mo_file)) {
                        load_textdomain($domain, $mo_file);
                    }
                }
            }
        }
    }
} 