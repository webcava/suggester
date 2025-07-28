<?php
/**
 * Main Suggester Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Suggester Class
 */
class Suggester {
    
    /**
     * Single instance of the class
     *
     * @var Suggester
     */
    private static $instance = null;
    
    /**
     * Get single instance of the class
     *
     * @return Suggester
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the plugin
     */
    private function init() {
        // Load autoloader
        $this->autoload();
        
        // Initialize admin
        if (is_admin()) {
            new Suggester_Admin();
        }
        
        // Initialize shortcode
        new Suggester_Shortcode();
        
        // Initialize statistics
        new Suggester_Statistics();
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
    }
    
    /**
     * Autoload plugin classes
     */
    private function autoload() {
        $classes = array(
            'Suggester_Admin' => 'admin/class-suggester-admin.php',
            'Suggester_Dashboard' => 'admin/class-suggester-dashboard.php',
            'Suggester_Overview' => 'admin/class-overview.php',
            'Suggester_Tools' => 'admin/class-suggester-tools.php',
            'Suggester_Settings' => 'admin/class-suggester-settings.php',
            'Suggester_Statistics' => 'admin/class-statistics.php',
            'Suggester_History' => 'admin/class-suggester-history.php',
            'Suggester_Other_Tools' => 'admin/class-suggester-other-tools.php',
            'Suggester_Help' => 'admin/class-suggester-help.php',
            'Suggester_Shortcode' => 'class-suggester-shortcode.php',
        );
        
        foreach ($classes as $class => $file) {
            $file_path = SUGGESTER_PLUGIN_DIR . 'includes/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // Toggle tool status
        add_action('wp_ajax_suggester_toggle_tool_status', array($this, 'ajax_toggle_tool_status'));
        
        // Duplicate tool
        add_action('wp_ajax_suggester_duplicate_tool', array($this, 'ajax_duplicate_tool'));
        
        // Generate suggestions (both for admin and frontend)
        add_action('wp_ajax_suggester_generate_suggestions', array($this, 'ajax_generate_suggestions'));
        add_action('wp_ajax_nopriv_suggester_generate_suggestions', array($this, 'ajax_generate_suggestions'));
        
        // Reset key statistics
        add_action('wp_ajax_suggester_reset_key_stats', array($this, 'ajax_reset_key_stats'));
    }
    
    /**
     * AJAX handler for toggling tool status
     */
    public function ajax_toggle_tool_status() {
        // Check for required data
        if (!isset($_POST['tool_id']) || !isset($_POST['active']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Missing required data');
        }
        
        // Sanitize input
        $tool_id = absint($_POST['tool_id']);
        $is_active = (bool) $_POST['active'];
        $nonce = sanitize_key($_POST['nonce']);
        
        // Verify nonce
        if (!wp_verify_nonce($nonce, 'suggester_toggle_tool_' . $tool_id)) {
            wp_send_json_error('Security check failed');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        // Get tool settings
        $tool_settings = get_option('suggester_tool_settings_' . $tool_id);
        if (!$tool_settings) {
            wp_send_json_error('Tool not found');
        }
        
        // Update status
        $tool_settings['active'] = $is_active;
        $tool_settings['modified'] = current_time('mysql');
        
        // Save settings
        if (update_option('suggester_tool_settings_' . $tool_id, $tool_settings)) {
            wp_send_json_success('Status updated successfully');
        } else {
            wp_send_json_error('Failed to update status');
        }
    }
    
    /**
     * AJAX handler for duplicating a tool
     */
    public function ajax_duplicate_tool() {
        // Check for required data
        if (!isset($_POST['tool_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Missing required data');
        }
        
        // Sanitize input
        $tool_id = absint($_POST['tool_id']);
        $nonce = sanitize_key($_POST['nonce']);
        
        // Verify nonce
        if (!wp_verify_nonce($nonce, 'suggester_duplicate_tool_' . $tool_id)) {
            wp_send_json_error('Security check failed');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        // Check if we're at the tool limit
        $tools = $this->get_all_tools();
        if (count($tools) >= Suggester_Tools::MAX_TOOLS) {
            wp_send_json_error('Maximum number of tools reached');
        }
        
        // Get source tool settings
        $source_settings = get_option('suggester_tool_settings_' . $tool_id);
        if (!$source_settings) {
            wp_send_json_error('Source tool not found');
        }
        
        // Get current tool counter
        $tool_counter = get_option('suggester_tool_counter', 0);
        
        // Increment counter
        $tool_counter++;
        update_option('suggester_tool_counter', $tool_counter);
        
        // Create duplicate settings
        $duplicate_settings = $source_settings;
        $duplicate_settings['name'] = $source_settings['name'] . ' ' . __('Copy', 'suggester');
        $duplicate_settings['created'] = current_time('mysql');
        $duplicate_settings['modified'] = current_time('mysql');
        
        // Save duplicate settings
        if (update_option('suggester_tool_settings_' . $tool_counter, $duplicate_settings)) {
            wp_send_json_success(array(
                'id' => $tool_counter,
                'message' => __('Tool duplicated successfully', 'suggester')
            ));
        } else {
            wp_send_json_error('Failed to duplicate tool');
        }
    }
    
    /**
     * AJAX handler for generating suggestions
     */
    public function ajax_generate_suggestions() {
        // Check for required data
        if (!isset($_POST['tool_id']) || !isset($_POST['keyword']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Missing required data');
        }
        
        // Sanitize input
        $tool_id = absint($_POST['tool_id']);
        $keyword = sanitize_text_field($_POST['keyword']);
        $nonce = sanitize_key($_POST['nonce']);
        $count = isset($_POST['count']) ? absint($_POST['count']) : 3;
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'English';
        
        // Cap count between 1 and 10
        $count = max(1, min(10, $count));
        
        // Verify nonce
        if (!wp_verify_nonce($nonce, 'suggester_frontend_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check if keyword is empty
        if (empty($keyword)) {
            wp_send_json_error('Please enter a keyword');
        }
        
        // Get tool settings
        $tool_settings = get_option('suggester_tool_settings_' . $tool_id);
        if (!$tool_settings) {
            wp_send_json_error('Tool not found');
        }
        
        // Check if tool is active
        if (empty($tool_settings['active'])) {
            wp_send_json_error('This suggestion tool is currently disabled');
        }
        
        // Get the default count from tool settings or use provided count
        $default_count = isset($tool_settings['default_count']) ? absint($tool_settings['default_count']) : 3;
        if (!isset($_POST['count'])) {
            $count = $default_count;
        }
        
        // Try to generate suggestions
        try {
            $suggestions = $this->generate_suggestions($tool_settings, $keyword, $count, $language, $tool_id);
            
            // Track successful tool usage
            $this->track_tool_usage($tool_id);
            
            wp_send_json_success(array(
                'suggestions' => $suggestions
            ));
        } catch (Exception $e) {
            // Log the error
            $statistics = new Suggester_Statistics();
            $statistics->log_error(
                $e->getMessage(),
                'api',
                'error',
                array(
                    'user_input' => $keyword,
                    'tool_name' => $tool_settings['name'] ?? 'Unknown',
                    'tool_id' => $tool_id
                )
            );
            
            wp_send_json_error(esc_html($e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for resetting key statistics
     */
    public function ajax_reset_key_stats() {
        // Check for required data
        if (!isset($_POST['tool_id']) || !isset($_POST['api_type']) || !isset($_POST['key_index']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Missing required data');
        }
        
        // Sanitize input
        $tool_id = absint($_POST['tool_id']);
        $api_type = sanitize_text_field($_POST['api_type']);
        $key_index = absint($_POST['key_index']);
        $nonce = sanitize_key($_POST['nonce']);
        
        // Verify nonce
        if (!wp_verify_nonce($nonce, 'suggester_reset_stats_' . $tool_id . '_' . $api_type . '_' . $key_index)) {
            wp_send_json_error('Security check failed');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        // Validate API type
        if (!in_array($api_type, array('gemini', 'openrouter'))) {
            wp_send_json_error('Invalid API type');
        }
        
        // Reset statistics
        $key_id = $tool_id . '_' . $api_type . '_' . $key_index;
        $default_stats = array(
            'uses' => 0,
            'failures' => 0,
            'total_tokens' => 0,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'cost' => 0,
            'last_use' => null
        );
        
        if (update_option('suggester_key_stats_' . $key_id, $default_stats)) {
            wp_send_json_success('Statistics reset successfully');
        } else {
            wp_send_json_error('Failed to reset statistics');
        }
    }
    
    /**
     * Generate suggestions using API
     *
     * @param array $tool_settings Tool settings
     * @param string $keyword Keyword to generate suggestions for
     * @param int $count Number of suggestions to generate
     * @param string $language Language for suggestions
     * @param int $tool_id The ID of the tool
     * @return array Array of suggestions
     * @throws Exception If generation fails
     */
    private function generate_suggestions($tool_settings, $keyword, $count, $language, $tool_id = 0) {
        // Get prompt template or use default
        $prompt_template = isset($tool_settings['prompt_template']) ? $tool_settings['prompt_template'] : 'Generate {count} creative suggestions for {keyword} in {language}. Each suggestion should be on a separate line and be concise (maximum 10 words each).';
        
        // Replace variables in prompt
        $prompt = str_replace(
            array('{keyword}', '{count}', '{language}'),
            array($keyword, $count, $language),
            $prompt_template
        );
        
        // Check if tool has custom API keys
        $use_custom_keys = isset($tool_settings['custom_api_keys']) && $tool_settings['custom_api_keys'];
        
        if (!$use_custom_keys) {
            // Fall back to global keys from Settings page
            $global_settings = get_option('suggester_global_settings', array());
            
            // Override tool settings with global settings
            $tool_settings['gemini_key'] = $global_settings['global_gemini_key'] ?? '';
            $tool_settings['openrouter_key_1'] = $global_settings['global_openrouter_key_1'] ?? '';
            $tool_settings['openrouter_model_1'] = $global_settings['global_openrouter_model_1'] ?? '';
            $tool_settings['openrouter_key_2'] = $global_settings['global_openrouter_key_2'] ?? '';
            $tool_settings['openrouter_model_2'] = $global_settings['global_openrouter_model_2'] ?? '';
            
            // Use 'global' as tool_id for tracking global key usage
            $tool_id = 'global';
        }
        
        // Collect all available keys for round-robin
        $available_keys = array();
        
        // Check Gemini key
        if (!empty($tool_settings['gemini_key'])) {
            $available_keys[] = array(
                'type' => 'gemini',
                'key' => $tool_settings['gemini_key'],
                'model' => null, // Gemini doesn't need a model parameter
                'ui_index' => 0 // Gemini uses index 0 in UI
            );
        }
        
        // Check OpenRouter key 1
        if (!empty($tool_settings['openrouter_key_1']) && !empty($tool_settings['openrouter_model_1'])) {
            $available_keys[] = array(
                'type' => 'openrouter',
                'key' => $tool_settings['openrouter_key_1'],
                'model' => $tool_settings['openrouter_model_1'],
                'ui_index' => 1 // OpenRouter Key 1 uses index 1 in UI
            );
        }
        
        // Check OpenRouter key 2
        if (!empty($tool_settings['openrouter_key_2']) && !empty($tool_settings['openrouter_model_2'])) {
            $available_keys[] = array(
                'type' => 'openrouter',
                'key' => $tool_settings['openrouter_key_2'],
                'model' => $tool_settings['openrouter_model_2'],
                'ui_index' => 2 // OpenRouter Key 2 uses index 2 in UI
            );
        }
        
        // If no keys are available, throw an error
        if (empty($available_keys)) {
            throw new Exception('No API keys are configured. Please add at least one API key in the tool settings.');
        }
        
        // Get the last used UI index and find the corresponding array position
        $last_ui_index = get_transient('suggester_last_key_index_' . $tool_id);
        $start_index = 0;
        
        if ($last_ui_index !== false) {
            // Find the array index of the last used UI index
            $last_array_index = -1;
            for ($i = 0; $i < count($available_keys); $i++) {
                if ($available_keys[$i]['ui_index'] == $last_ui_index) {
                    $last_array_index = $i;
                    break;
                }
            }
            
            // Start from the next available key in round-robin fashion
            if ($last_array_index >= 0) {
                $start_index = ($last_array_index + 1) % count($available_keys);
            }
        }
        
        // Track failed keys to avoid infinite loops
        $failed_keys = array();
        $current_index = $start_index;
        
        // Try keys in round-robin fashion until one works or all fail
        while (count($failed_keys) < count($available_keys)) {
            $key_info = $available_keys[$current_index];
            
            // Skip already failed keys
            if (in_array($current_index, $failed_keys)) {
                $current_index = ($current_index + 1) % count($available_keys);
                continue;
            }
            
            try {
                $result = null;
                
                // Call the appropriate API based on key type
                if ($key_info['type'] === 'openrouter') {
                    $result = $this->call_openrouter_api($key_info['key'], $key_info['model'], $prompt, $tool_id, $key_info['ui_index'], $keyword);
                } elseif ($key_info['type'] === 'gemini') {
                    $result = $this->call_gemini_api($key_info['key'], $prompt, $tool_id, $key_info['ui_index'], $keyword);
                }
                
                // If successful, update the last used key index and return the result
                set_transient('suggester_last_key_index_' . $tool_id, $key_info['ui_index'], 3600); // Store for 1 hour
                return $result;
                
            } catch (Exception $e) {
                // If this key failed, add it to the failed keys and try the next one
                $failed_keys[] = $current_index;
                
                // Store the specific error for this key
                set_transient('suggester_key_error_' . $tool_id . '_' . $key_info['type'] . '_' . $key_info['ui_index'], 
                              $e->getMessage(), 300); // Store error for 5 minutes
                
                // Move to the next key
                $current_index = ($current_index + 1) % count($available_keys);
            }
        }
        
        // If all keys failed, throw a comprehensive error message
        $error_messages = array();
        foreach ($failed_keys as $index) {
            $key_info = $available_keys[$index];
            $key_type = ucfirst($key_info['type']);
            $error = get_transient('suggester_key_error_' . $tool_id . '_' . $key_info['type'] . '_' . $key_info['ui_index']);
            $error_messages[] = "$key_type API: " . ($error ?: 'Unknown error');
        }
        
        throw new Exception('All API keys failed. Errors: ' . esc_html(implode('; ', $error_messages)));
    }
    
    /**
     * Call OpenRouter API
     *
     * @param string $api_key OpenRouter API key
     * @param string $model Model to use
     * @param string $prompt Prompt to send
     * @param int $tool_id Tool ID for tracking
     * @param int $key_index Key index for tracking
     * @param string $original_keyword Original user keyword for error logging
     * @return array Array of suggestions
     * @throws Exception If API call fails
     */
    private function call_openrouter_api($api_key, $model, $prompt, $tool_id = 0, $key_index = 0, $original_keyword = '') {
        $url = 'https://openrouter.ai/api/v1/chat/completions';
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 300,
            'temperature' => 0.7,
            'usage' => array(
                'include' => true
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
                'HTTP-Referer' => home_url(),
                'X-Title' => get_bloginfo('name') . ' - Suggester Plugin'
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            // Track failure
            $error_msg = 'OpenRouter API error: ' . $response->get_error_message();
            $this->track_key_failure($tool_id, 'openrouter', $key_index, $error_msg, $original_keyword);
            throw new Exception(esc_html($error_msg));
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            // Track failure
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            $error_msg = "OpenRouter API error ($status_code): $error_message";
            $this->track_key_failure($tool_id, 'openrouter', $key_index, $error_msg, $original_keyword);
            throw new Exception(esc_html($error_msg));
        }
        
        // Get content from response
        if (!isset($data['choices'][0]['message']['content'])) {
            // Track failure
            $error_msg = 'Invalid response from OpenRouter API';
            $this->track_key_failure($tool_id, 'openrouter', $key_index, $error_msg, $original_keyword);
            throw new Exception(esc_html($error_msg));
        }
        
        $content = $data['choices'][0]['message']['content'];
        
        // Process the response to extract suggestions
        $lines = explode("\n", $content);
        $suggestions = array();
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Remove list markers (1., -, *, etc.)
            $line = preg_replace('/^(\d+\.\s+|\-\s+|\*\s+)/', '', $line);
            
            // Skip lines that are likely part of an explanation
            if (stripos($line, 'here') === 0 || stripos($line, 'following') === 0 || stripos($line, 'are') === 0) {
                continue;
            }
            
            $suggestions[] = $line;
        }
        
        if (empty($suggestions)) {
            // Track failure
            $error_msg = 'No valid suggestions found in API response';
            $this->track_key_failure($tool_id, 'openrouter', $key_index, $error_msg, $original_keyword);
            throw new Exception(esc_html($error_msg));
        }
        
        // Track successful usage with token data
        $usage_data = array();
        if (isset($data['usage'])) {
            $usage_data = array(
                'prompt_tokens' => isset($data['usage']['prompt_tokens']) ? intval($data['usage']['prompt_tokens']) : 0,
                'completion_tokens' => isset($data['usage']['completion_tokens']) ? intval($data['usage']['completion_tokens']) : 0,
                'total_tokens' => isset($data['usage']['total_tokens']) ? intval($data['usage']['total_tokens']) : 0,
                'cost' => isset($data['usage']['cost']) ? floatval($data['usage']['cost']) : 0
            );
        }
        
        $this->track_key_usage($tool_id, 'openrouter', $key_index, $usage_data);
        
        return $suggestions;
    }
    
    /**
     * Call Gemini API
     *
     * @param string $api_key Gemini API key
     * @param string $prompt Prompt to send
     * @param int $tool_id Tool ID for tracking
     * @param int $key_index Key index for tracking
     * @param string $original_keyword Original user keyword for error logging
     * @return array Array of suggestions
     * @throws Exception If API call fails
     */
    private function call_gemini_api($api_key, $prompt, $tool_id = 0, $key_index = 0, $original_keyword = '') {
        $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=' . urlencode($api_key);
        
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'maxOutputTokens' => 300
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            // Track failure
            $error_msg = 'Gemini API error: ' . $response->get_error_message();
            $this->track_key_failure($tool_id, 'gemini', $key_index, $error_msg, $original_keyword);
            throw new Exception(esc_html($error_msg));
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            // Track failure
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            $error_msg = "Gemini API error ($status_code): $error_message";
            $this->track_key_failure($tool_id, 'gemini', $key_index, $error_msg, $original_keyword);
            throw new Exception(esc_html($error_msg));
        }
        
        // Get content from response
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            // Track failure
            $error_msg = 'Invalid response from Gemini API';
            $this->track_key_failure($tool_id, 'gemini', $key_index, $error_msg, $original_keyword);
            throw new Exception(esc_html($error_msg));
        }
        
        $content = $data['candidates'][0]['content']['parts'][0]['text'];
        
        // Process the response to extract suggestions
        $lines = explode("\n", $content);
        $suggestions = array();
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Remove list markers (1., -, *, etc.)
            $line = preg_replace('/^(\d+\.\s+|\-\s+|\*\s+)/', '', $line);
            
            // Skip lines that are likely part of an explanation
            if (stripos($line, 'here') === 0 || stripos($line, 'following') === 0 || stripos($line, 'are') === 0) {
                continue;
            }
            
            $suggestions[] = $line;
        }
        
        if (empty($suggestions)) {
            // Track failure
            $error_msg = 'No valid suggestions found in API response';
            $this->track_key_failure($tool_id, 'gemini', $key_index, $error_msg, $original_keyword);
            throw new Exception(esc_html($error_msg));
        }
        
        // Track successful usage (Gemini doesn't provide token counts)
        $this->track_key_usage($tool_id, 'gemini', $key_index);
        
        return $suggestions;
    }
    
    /**
     * Get all tools
     */
    private function get_all_tools() {
        $tools = array();
        $tool_counter = get_option('suggester_tool_counter', 0);
        
        for ($i = 1; $i <= $tool_counter; $i++) {
            $tool_settings = get_option('suggester_tool_settings_' . $i);
            if ($tool_settings) {
                $tools[$i] = $tool_settings;
            }
        }
        
        return $tools;
    }
    
    /**
     * Track successful API key usage
     *
     * @param int $tool_id Tool ID
     * @param string $api_type API type (openrouter, gemini)
     * @param int $key_index Key index
     * @param array $usage_data Usage data (tokens, cost, etc.)
     */
    private function track_key_usage($tool_id, $api_type, $key_index, $usage_data = array()) {
        $key_id = $tool_id . '_' . $api_type . '_' . $key_index;
        $stats = get_option('suggester_key_stats_' . $key_id, array(
            'uses' => 0,
            'failures' => 0,
            'total_tokens' => 0,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'cost' => 0,
            'last_use' => null
        ));
        
        // Increment usage count
        $stats['uses']++;
        $stats['last_use'] = current_time('mysql');
        
        // Add token data for OpenRouter
        if ($api_type === 'openrouter' && !empty($usage_data)) {
            $stats['total_tokens'] += isset($usage_data['total_tokens']) ? $usage_data['total_tokens'] : 0;
            $stats['prompt_tokens'] += isset($usage_data['prompt_tokens']) ? $usage_data['prompt_tokens'] : 0;
            $stats['completion_tokens'] += isset($usage_data['completion_tokens']) ? $usage_data['completion_tokens'] : 0;
            $stats['cost'] += isset($usage_data['cost']) ? $usage_data['cost'] : 0;
        }
        
        update_option('suggester_key_stats_' . $key_id, $stats);
        
        // Clear any existing error transients for this key since it's now working
        delete_transient('suggester_key_error_' . $tool_id . '_' . $api_type . '_' . $key_index);
    }
    
    /**
     * Track API key failure
     *
     * @param int $tool_id Tool ID
     * @param string $api_type API type (openrouter, gemini)
     * @param int $key_index Key index
     * @param string $error_message Optional error message to log
     * @param string $user_input Optional user input that caused the error
     */
    private function track_key_failure($tool_id, $api_type, $key_index, $error_message = '', $user_input = '') {
        $key_id = $tool_id . '_' . $api_type . '_' . $key_index;
        $stats = get_option('suggester_key_stats_' . $key_id, array(
            'uses' => 0,
            'failures' => 0,
            'total_tokens' => 0,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'cost' => 0,
            'last_use' => null
        ));
        
        // Increment failure count
        $stats['failures']++;
        
        update_option('suggester_key_stats_' . $key_id, $stats);
        
        // Log the failure to the error table using the standardized logging system
        if (!empty($error_message)) {
            // Get tool name for context
            $tool_name = 'Unknown Tool';
            if ($tool_id === 'global') {
                $tool_name = 'Global Settings';
            } else {
                $tool_settings = get_option('suggester_tool_settings_' . $tool_id);
                if ($tool_settings && isset($tool_settings['name'])) {
                    $tool_name = $tool_settings['name'];
                }
            }
            
            // Log the error directly to the database
            $this->log_api_key_failure($tool_id, $api_type, $key_index, $error_message, $user_input, $tool_name);
        }
    }
    
    /**
     * Get API key statistics
     *
     * @param int $tool_id Tool ID
     * @param string $api_type API type (openrouter, gemini)
     * @param int $key_index Key index
     * @return array Statistics array
     */
    public function get_key_stats($tool_id, $api_type, $key_index) {
        $key_id = $tool_id . '_' . $api_type . '_' . $key_index;
        return get_option('suggester_key_stats_' . $key_id, array(
            'uses' => 0,
            'failures' => 0,
            'total_tokens' => 0,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'cost' => 0,
            'last_use' => null
        ));
    }
    
    /**
     * Track tool usage (each time user clicks "Suggest" button)
     *
     * @param int $tool_id Tool ID
     */
    private function track_tool_usage($tool_id) {
        if ($tool_id > 0) {
            $tool_stats = get_option('suggester_tool_stats_' . $tool_id, array(
                'uses' => 0,
                'favorites' => 0,
                'copies' => 0,
                'created_date' => current_time('mysql')
            ));
            
            $tool_stats['uses']++;
            
            update_option('suggester_tool_stats_' . $tool_id, $tool_stats);
            
            // Also log to usage tracking table for time-based statistics
            $this->log_usage_event($tool_id);
        }
    }
    
    /**
     * Log usage event to the usage tracking table
     *
     * @param int $tool_id Tool ID
     */
    private function log_usage_event($tool_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_usage';
        
        // Get tool name
        $tool_settings = get_option('suggester_tool_settings_' . $tool_id);
        $tool_name = isset($tool_settings['name']) ? $tool_settings['name'] : 'Unknown Tool';
        
        // Get user IP
        $ip_address = $this->get_user_ip();
        
        // Insert usage event
        $wpdb->insert(
            $table_name,
            array(
                'tool_id' => $tool_id,
                'tool_name' => $tool_name,
                'action_type' => 'usage', // Specify this is a usage event
                'user_id' => get_current_user_id(),
                'ip_address' => $ip_address,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 500) : null,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
    }
    
    /**
     * Log API key failure directly to the error table
     *
     * @param int|string $tool_id Tool ID
     * @param string $api_type API type (openrouter, gemini)
     * @param int $key_index Key index
     * @param string $error_message Error message to log
     * @param string $user_input User input that caused the error
     * @param string $tool_name Tool name for context
     */
    private function log_api_key_failure($tool_id, $api_type, $key_index, $error_message, $user_input, $tool_name) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_errors';
        
        // Ensure the error table exists
        $this->maybe_create_error_table();
        
        // Check if table exists after creation
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            // Force debug log this critical error
            error_log("SUGGESTER ERROR: Error table '$table_name' does not exist and could not be created!");
            return false;
        }
        
        // Generate key field name for API errors
        $key_field_name = null;
        if ($api_type === 'gemini') {
            $key_field_name = 'Gemini API Key';
        } elseif ($api_type === 'openrouter') {
            if ($key_index == 1) {
                $key_field_name = 'OpenRouter Key 1';
            } elseif ($key_index == 2) {
                $key_field_name = 'OpenRouter Key 2';
            } else {
                $key_field_name = 'OpenRouter Key';
            }
        }
        
        // Prepare error data
        $error_data = array(
            'error_message' => sanitize_text_field($error_message),
            'error_type' => 'api_key_failure',
            'severity' => 'error',
            'user_id' => get_current_user_id(),
            'user_input' => $user_input ? sanitize_textarea_field($user_input) : null,
            'tool_name' => sanitize_text_field($tool_name),
            'tool_id' => is_numeric($tool_id) ? absint($tool_id) : null,
            'key_field_name' => $key_field_name,
            'api_type' => sanitize_key($api_type),
            'key_index' => absint($key_index),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 500) : null,
            'created_at' => current_time('mysql')
        );
        
        // Insert error directly into the database
        $result = $wpdb->insert($table_name, $error_data);
        
        // Always log this for debugging (regardless of WP_DEBUG)
        error_log("SUGGESTER API FAILURE LOG ATTEMPT: " . json_encode(array(
            'result' => $result,
            'insert_id' => $wpdb->insert_id,
            'error_message' => $error_message,
            'api_type' => $api_type,
            'key_index' => $key_index,
            'tool_name' => $tool_name,
            'user_input' => $user_input,
            'wpdb_last_error' => $wpdb->last_error,
            'table_exists' => $table_exists
        )));
        
        return $result !== false;
    }
    
    /**
     * Create error logging table if it doesn't exist
     */
    private function maybe_create_error_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_errors';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                error_message text NOT NULL,
                error_type varchar(50) NOT NULL,
                severity varchar(20) NOT NULL DEFAULT 'error',
                user_id bigint(20) DEFAULT NULL,
                user_input text DEFAULT NULL,
                tool_name varchar(100) DEFAULT NULL,
                tool_id int(11) DEFAULT NULL,
                key_field_name varchar(100) DEFAULT NULL,
                api_type varchar(20) DEFAULT NULL,
                key_index int(11) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY error_type (error_type),
                KEY severity (severity),
                KEY created_at (created_at),
                KEY user_id (user_id),
                KEY tool_id (tool_id),
                KEY api_type (api_type)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Force creation of plugin tables (public method for debugging)
     * 
     * @return array Result of table creation
     */
    public function force_create_tables() {
        return self::create_plugin_tables();
    }
    
    /**
     * Create error and usage tracking tables
     */
    private static function create_plugin_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $results = array();
        
        // Create error table
        $error_table_name = $wpdb->prefix . 'suggester_errors';
        if ($wpdb->get_var("SHOW TABLES LIKE '$error_table_name'") != $error_table_name) {
            $sql = "CREATE TABLE $error_table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                error_message text NOT NULL,
                error_type varchar(50) NOT NULL,
                severity varchar(20) NOT NULL DEFAULT 'error',
                user_id bigint(20) DEFAULT NULL,
                user_input text DEFAULT NULL,
                tool_name varchar(100) DEFAULT NULL,
                tool_id int(11) DEFAULT NULL,
                key_field_name varchar(100) DEFAULT NULL,
                api_type varchar(20) DEFAULT NULL,
                key_index int(11) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY error_type (error_type),
                KEY severity (severity),
                KEY created_at (created_at),
                KEY user_id (user_id),
                KEY tool_id (tool_id),
                KEY api_type (api_type)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $results['error_table'] = dbDelta($sql);
            
            // Verify error table creation
            $error_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$error_table_name'") == $error_table_name;
            $results['error_table_created'] = $error_table_exists;
            
            // Log table creation attempt
            error_log("SUGGESTER TABLE CREATION: Error table creation attempt. Result: " . json_encode(array(
                'table_name' => $error_table_name,
                'exists_after_creation' => $error_table_exists,
                'dbdelta_result' => $results['error_table'],
                'wpdb_last_error' => $wpdb->last_error
            )));
        } else {
            $results['error_table'] = 'already_exists';
            $results['error_table_created'] = true;
        }
        
        // Create usage tracking table
        $usage_table_name = $wpdb->prefix . 'suggester_usage';
        if ($wpdb->get_var("SHOW TABLES LIKE '$usage_table_name'") != $usage_table_name) {
            $sql = "CREATE TABLE $usage_table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                tool_id int(11) NOT NULL,
                tool_name varchar(100) DEFAULT NULL,
                action_type varchar(20) DEFAULT 'usage',
                user_id bigint(20) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY tool_id (tool_id),
                KEY action_type (action_type),
                KEY created_at (created_at),
                KEY user_id (user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $results['usage_table'] = dbDelta($sql);
            
            // Verify usage table creation
            $usage_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$usage_table_name'") == $usage_table_name;
            $results['usage_table_created'] = $usage_table_exists;
            
            // Check if action_type column exists, if not add it
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $usage_table_name LIKE 'action_type'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $usage_table_name ADD COLUMN action_type varchar(20) DEFAULT 'usage' AFTER tool_name");
                $wpdb->query("ALTER TABLE $usage_table_name ADD INDEX action_type (action_type)");
                $results['action_type_column_added'] = true;
            }
        } else {
            $results['usage_table'] = 'already_exists';
            $results['usage_table_created'] = true;
            
            // Check if action_type column exists, if not add it
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $usage_table_name LIKE 'action_type'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $usage_table_name ADD COLUMN action_type varchar(20) DEFAULT 'usage' AFTER tool_name");
                $wpdb->query("ALTER TABLE $usage_table_name ADD INDEX action_type (action_type)");
                $results['action_type_column_added'] = true;
            }
        }
        
        return $results;
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Set default options
        if (!get_option('suggester_version')) {
            add_option('suggester_version', SUGGESTER_VERSION);
        }
        
        // Initialize default statistics
        if (!get_option('suggester_daily_count')) {
            add_option('suggester_daily_count', 0);
            add_option('suggester_weekly_count', 0);
            add_option('suggester_monthly_count', 0);
            add_option('suggester_yearly_count', 0);
            add_option('suggester_last_reset_dates', array(
                'daily' => current_time('Y-m-d'),
                'weekly' => current_time('Y-m-d'),
                'monthly' => current_time('Y-m'),
                'yearly' => current_time('Y')
            ));
        }
        
        // Initialize tool counter
        if (!get_option('suggester_tool_counter')) {
            add_option('suggester_tool_counter', 0);
        }
        
        // Create error and usage tracking tables
        self::create_plugin_tables();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clean up if needed
        // Note: We don't delete options on deactivation to preserve user data
    }
} 