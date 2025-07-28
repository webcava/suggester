<?php
/**
 * Templates Index
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all available templates
 * 
 * @return array Registered templates
 */
function suggester_register_templates() {
    // Include template files
    require_once SUGGESTER_PLUGIN_DIR . 'assets/templates/night-mode/template.php';
    require_once SUGGESTER_PLUGIN_DIR . 'assets/templates/light/template.php';
    require_once SUGGESTER_PLUGIN_DIR . 'assets/templates/vived/template.php';
    
    // Register templates
    $templates = array(
        'night-mode' => array(
            'info' => suggester_night_mode_template(),
            'render_callback' => 'suggester_render_night_mode_template',
            'assets' => array(
                'css' => 'night-mode/style.css',
                'js' => 'night-mode/script.js',
            ),
        ),
        'light' => array(
            'info' => suggester_light_template(),
            'render_callback' => 'suggester_render_light_template',
            'assets' => array(
                'css' => 'light/style.css',
                'js' => 'light/script.js',
            ),
        ),
        'vived' => array(
            'info' => suggester_vived_template(),
            'render_callback' => 'suggester_render_vived_template',
            'assets' => array(
                'css' => 'vived/style.css',
                'js' => 'vived/script.js',
            ),
        ),
        // Add more templates here in the future
    );
    
    return apply_filters('suggester_templates', $templates);
}

/**
 * Get template info for all templates
 * 
 * @return array Template info for displaying in the admin
 */
function suggester_get_templates_info() {
    $templates = suggester_register_templates();
    $template_info = array();
    
    foreach ($templates as $template_id => $template) {
        $template_info[$template_id] = $template['info'];
    }
    
    return $template_info;
}

/**
 * Get template info by ID
 * 
 * @param string $template_id Template ID
 * @return array|false Template info or false if not found
 */
function suggester_get_template_info($template_id) {
    $templates = suggester_register_templates();
    
    if (isset($templates[$template_id])) {
        return $templates[$template_id]['info'];
    }
    
    return false;
}

/**
 * Render template by ID
 * 
 * @param string $template_id Template ID
 * @param array $settings Tool settings
 * @return string Template HTML
 */
function suggester_render_template($template_id, $settings) {
    $templates = suggester_register_templates();
    
    if (isset($templates[$template_id]) && is_callable($templates[$template_id]['render_callback'])) {
        return call_user_func($templates[$template_id]['render_callback'], $settings);
    }
    
    return '';
}

/**
 * Enqueue template assets
 * 
 * @param string $template_id Template ID
 */
function suggester_enqueue_template_assets($template_id) {
    $templates = suggester_register_templates();
    
    if (isset($templates[$template_id])) {
        $template = $templates[$template_id];
        
        // Enqueue CSS
        if (isset($template['assets']['css'])) {
            wp_enqueue_style(
                'suggester-template-' . $template_id,
                SUGGESTER_PLUGIN_URL . 'assets/templates/' . $template['assets']['css'],
                array(),
                SUGGESTER_VERSION
            );
        }
        
        // Enqueue JS
        if (isset($template['assets']['js'])) {
            wp_enqueue_script(
                'suggester-template-' . $template_id,
                SUGGESTER_PLUGIN_URL . 'assets/templates/' . $template['assets']['js'],
                array(),
                SUGGESTER_VERSION,
                true
            );
        }
    }
} 