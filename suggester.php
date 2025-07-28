<?php
/**
 * Plugin Name: Suggester
 * Description: An intelligent suggestion generator based on keywords using Google Gemini and OpenRouter APIs.
 * Version: 1.0.1
 * Author: webcava
 * Author URI: https://webcava.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: suggester
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SUGGESTER_VERSION', '1.0.1');
define('SUGGESTER_PLUGIN_FILE', __FILE__);
define('SUGGESTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SUGGESTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SUGGESTER_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Enhanced textdomain loading function with comprehensive Arabic support
function suggester_load_textdomain() {
    $locale = get_locale();
    $domain = 'suggester';
    $plugin_rel_path = dirname(plugin_basename(__FILE__)) . '/languages';
    $plugin_abs_path = SUGGESTER_PLUGIN_DIR . 'languages';
    
    // Allow forcing Arabic language for Suggester plugin
    $force_arabic = apply_filters('suggester_force_arabic', false);
    $suggester_lang = sanitize_key($_GET['suggester_lang'] ?? '');
    if ($force_arabic || (isset($_GET['suggester_lang']) && $suggester_lang === 'ar')) {
        $locale = 'ar';
    }
    
    // Debug information
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Suggester: Attempting to load textdomain for locale '{$locale}'");
        error_log("Suggester: Plugin path '{$plugin_rel_path}'");
        error_log("Suggester: Absolute path '{$plugin_abs_path}'");
        error_log("Suggester: Force Arabic: " . ($force_arabic ? 'Yes' : 'No'));
    }
    
    // First, try the standard WordPress method
    $loaded = load_plugin_textdomain($domain, false, $plugin_rel_path);
    
    // If not loaded and locale contains Arabic variants, try different approaches
    if (!$loaded && strpos($locale, 'ar') !== false) {
        // List of Arabic locale files to try
        $arabic_files = array(
            $plugin_abs_path . '/' . $domain . '-' . $locale . '.mo',        // Full locale (e.g., ar_SA)
            $plugin_abs_path . '/' . $domain . '-ar.mo',                     // Base Arabic
            $plugin_abs_path . '/' . $domain . '-ar_SA.mo',                  // Saudi Arabic
            $plugin_abs_path . '/' . $domain . '-ar_EG.mo',                  // Egyptian Arabic
            $plugin_abs_path . '/' . $domain . '-ar_AE.mo',                  // UAE Arabic
        );
        
        foreach ($arabic_files as $mo_file) {
            if (file_exists($mo_file)) {
                $loaded = load_textdomain($domain, $mo_file);
                if ($loaded) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Suggester: Successfully loaded Arabic translation from '{$mo_file}'");
                    }
                    break;
                }
            }
        }
    }
    
    // Force load for Arabic if still not loaded
    if (!$loaded && (strpos($locale, 'ar') !== false || is_rtl() || $force_arabic)) {
        $ar_mo_file = $plugin_abs_path . '/suggester-ar.mo';
        if (file_exists($ar_mo_file)) {
            $loaded = load_textdomain($domain, $ar_mo_file);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Suggester: Force-loaded Arabic translation: " . ($loaded ? 'SUCCESS' : 'FAILED'));
            }
        }
    }
    
    // Final attempt with unload_textdomain and reload
    if (!$loaded && strpos($locale, 'ar') !== false) {
        unload_textdomain($domain);
        $loaded = load_plugin_textdomain($domain, false, $plugin_rel_path);
    }
    
    // Log final result
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Suggester: Final textdomain loading result for locale '{$locale}': " . ($loaded ? 'SUCCESS' : 'FAILED'));
        
        // Test a sample translation
        $test_translation = __('Settings', 'suggester');
        error_log("Suggester: Test translation 'Settings' → '{$test_translation}'");
    }
    
    return $loaded;
}

// Load textdomain very early
add_action('plugins_loaded', 'suggester_load_textdomain', 1);

// Also try loading on init for admin
add_action('init', function() {
    if (is_admin()) {
        suggester_load_textdomain();
    }
}, 5);

// Load on admin_init as well for backend
add_action('admin_init', 'suggester_load_textdomain', 1);

// Load the main plugin class
require_once SUGGESTER_PLUGIN_DIR . 'includes/class-suggester.php';

// Initialize the plugin
function suggester_init() {
    return Suggester::get_instance();
}

// Hook into WordPress (after textdomain is loaded)
add_action('plugins_loaded', 'suggester_init', 10);

// Activation hook
register_activation_hook(__FILE__, array('Suggester', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('Suggester', 'deactivate'));

// Function to enable Arabic mode for Suggester
function suggester_enable_arabic_mode() {
    add_filter('suggester_force_arabic', '__return_true');
}

// Allow users to easily enable Arabic mode
// Usage: add_action('init', 'suggester_enable_arabic_mode');
// Or add ?suggester_lang=ar to the URL 