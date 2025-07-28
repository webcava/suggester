<?php
/**
 * Suggester Settings Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Class
 */
class Suggester_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->process_form_submissions();
    }
    
    /**
     * Process form submissions
     */
    private function process_form_submissions() {
        // Check if this is a settings save
        $action = sanitize_text_field(wp_unslash($_POST['action'] ?? ''));
        if (isset($_POST['action']) && $action === 'save_settings') {
            // Verify nonce
            if (isset($_POST['suggester_settings_nonce']) && wp_verify_nonce(sanitize_key($_POST['suggester_settings_nonce']), 'suggester_save_settings')) {
                // Get existing settings
                $global_settings = get_option('suggester_global_settings', array());
                
                // Update global API keys
                if (isset($_POST['global_gemini_key'])) {
                    $global_settings['global_gemini_key'] = sanitize_text_field($_POST['global_gemini_key']);
                }
                
                if (isset($_POST['global_openrouter_key_1'])) {
                    $global_settings['global_openrouter_key_1'] = sanitize_text_field($_POST['global_openrouter_key_1']);
                }
                
                if (isset($_POST['global_openrouter_model_1'])) {
                    $global_settings['global_openrouter_model_1'] = sanitize_text_field($_POST['global_openrouter_model_1']);
                }
                
                if (isset($_POST['global_openrouter_key_2'])) {
                    $global_settings['global_openrouter_key_2'] = sanitize_text_field($_POST['global_openrouter_key_2']);
                }
                
                if (isset($_POST['global_openrouter_model_2'])) {
                    $global_settings['global_openrouter_model_2'] = sanitize_text_field($_POST['global_openrouter_model_2']);
                }
                
                // Update modified timestamp
                $global_settings['modified'] = current_time('mysql');
                
                // Save settings
                update_option('suggester_global_settings', $global_settings);
                
                // Store success message in transient
                set_transient('suggester_admin_message', array(
                    'type' => 'success',
                    'message' => __('Global settings saved successfully.', 'suggester')
                ), 60);
                
                // Use JavaScript redirect instead of wp_redirect to avoid headers issue
                add_action('admin_footer', function() {
                    $redirect_url = add_query_arg(
                        array('_success' => '1'),
                        admin_url('admin.php?page=suggester-settings')
                    );
                    echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
                });
            }
        }
    }
    
    /**
     * Render the settings page
     */
    public function render() {
        // Check for success message
        if (isset($_GET['_success']) && $_GET['_success'] === '1') {
            $message = get_transient('suggester_admin_message');
            if ($message) {
                echo '<div class="notice notice-' . esc_attr($message['type']) . ' is-dismissible"><p>' . 
                      esc_html($message['message']) . 
                      '</p></div>';
                delete_transient('suggester_admin_message');
            }
        }
        
        // Get global settings
        $global_settings = get_option('suggester_global_settings', array());
        
        ?>
        <div class="wrap suggester-admin-page">
            <h1><?php esc_html_e('Settings', 'suggester'); ?></h1>
            
            <div class="suggester-settings-container">
                <form id="suggester-settings-form" method="post" action="">
                    <?php wp_nonce_field('suggester_save_settings', 'suggester_settings_nonce'); ?>
                    <input type="hidden" name="action" value="save_settings">
                    
                    <!-- API Key Settings Section -->
                    <div class="suggester-settings-section">
                        <div class="suggester-section-header">
                            <h2><?php esc_html_e('API Key Settings', 'suggester'); ?></h2>
                            <p class="description">
                                <?php esc_html_e('Configure global API keys that will be used by default for all tools. Tools can override these settings by enabling "Custom API Keys" in their individual settings.', 'suggester'); ?>
                            </p>
                        </div>
                        
                        <div class="suggester-section-content">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="suggester-global-gemini-key">
                                            <?php esc_html_e('Google Gemini API Key', 'suggester'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <div class="suggester-api-key-field">
                                            <input type="password" id="suggester-global-gemini-key" 
                                                   name="global_gemini_key" 
                                                   value="<?php echo esc_attr($global_settings['global_gemini_key'] ?? ''); ?>"
                                                   class="regular-text suggester-key-input">
                                            <button type="button" class="button suggester-toggle-visibility">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </button>
                                        </div>
                                        <?php
                                        // Show status indicators and tracking for this key
                                        $last_key_index = get_transient('suggester_last_key_index_global');
                                        $key_error = get_transient('suggester_key_error_global_gemini_0');
                                        
                                        if ($last_key_index == 0) {
                                            echo '<span class="suggester-key-status suggester-key-last-used">' . esc_html__('Last used', 'suggester') . '</span>';
                                        }
                                        
                                        if ($key_error) {
                                            echo '<span class="suggester-key-status suggester-key-error">' . esc_html__('Error: ', 'suggester') . esc_html(substr($key_error, 0, 50)) . (strlen($key_error) > 50 ? '...' : '') . '</span>';
                                        }
                                        
                                        // Get tracking statistics
                                        $suggester = Suggester::get_instance();
                                        $stats = $suggester->get_key_stats('global', 'gemini', 0);
                                        
                                        if ($stats['uses'] > 0 || $stats['failures'] > 0) {
                                            $last_use = $stats['last_use'] ? human_time_diff(strtotime($stats['last_use'])) . ' ago' : 'Never';
                                            echo '<div class="suggester-key-tracking">';
                                            echo '<div class="suggester-tracking-info">';
                                            echo '<strong>' . esc_html__('Tracking:', 'suggester') . '</strong> ';
                                            echo sprintf(
                                                /* translators: %1$d: number of API uses, %2$d: number of failures, %3$s: last use time */
                                                esc_html__('Uses: %1$d | Failures: %2$d | Last Use: %3$s', 'suggester'),
                                                esc_html($stats['uses']),
                                                esc_html($stats['failures']),
                                                esc_html($last_use)
                                            );
                                            echo '</div>';
                                            echo '<button type="button" class="button button-small suggester-reset-stats" ';
                                            echo 'data-tool-id="global" ';
                                            echo 'data-api-type="gemini" ';
                                            echo 'data-key-index="0" ';
                                            echo 'data-nonce="' . esc_attr(wp_create_nonce('suggester_reset_stats_global_gemini_0')) . '" ';
                                            echo 'title="' . esc_attr__('Reset tracking statistics', 'suggester') . '">';
                                            echo '<span class="dashicons dashicons-update-alt"></span>';
                                            echo '</button>';
                                            echo '</div>';
                                        }
                                        ?>
                                        <p class="description">
                                            <?php esc_html_e('Enter your Google Gemini API key. Get one at https://ai.google.dev/', 'suggester'); ?><br>
                                            <?php esc_html_e('Leave this field empty to disable this key globally.', 'suggester'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="suggester-global-openrouter-key-1">
                                            <?php esc_html_e('OpenRouter API Key 1', 'suggester'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <div class="suggester-api-key-field">
                                            <input type="password" id="suggester-global-openrouter-key-1" 
                                                   name="global_openrouter_key_1" 
                                                   value="<?php echo esc_attr($global_settings['global_openrouter_key_1'] ?? ''); ?>"
                                                   class="regular-text suggester-key-input">
                                            <button type="button" class="button suggester-toggle-visibility">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </button>
                                            
                                            <select name="global_openrouter_model_1" class="suggester-model-select">
                                                <option value=""><?php esc_html_e('Select Model', 'suggester'); ?></option>
                                                <optgroup label="<?php esc_html_e('OpenAI', 'suggester'); ?>">
                                                    <option value="openai/gpt-4o" <?php selected($global_settings['global_openrouter_model_1'] ?? '', 'openai/gpt-4o'); ?>>GPT-4o</option>
                                                    <option value="openai/gpt-4o-mini" <?php selected($global_settings['global_openrouter_model_1'] ?? '', 'openai/gpt-4o-mini'); ?>>GPT-4o Mini</option>
                                                    <option value="openai/gpt-4" <?php selected($global_settings['global_openrouter_model_1'] ?? '', 'openai/gpt-4'); ?>>GPT-4</option>
                                                    <option value="openai/gpt-3.5-turbo" <?php selected($global_settings['global_openrouter_model_1'] ?? '', 'openai/gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                                </optgroup>
                                                <optgroup label="<?php esc_html_e('Anthropic', 'suggester'); ?>">
                                                    <option value="anthropic/claude-opus-4" <?php selected($global_settings['global_openrouter_model_1'] ?? '', 'anthropic/claude-opus-4'); ?>>Claude Opus 4</option>
                                                    <option value="anthropic/claude-sonnet-4" <?php selected($global_settings['global_openrouter_model_1'] ?? '', 'anthropic/claude-sonnet-4'); ?>>Claude Sonnet 4</option>
                                                    <option value="anthropic/claude-3-opus" <?php selected($global_settings['global_openrouter_model_1'] ?? '', 'anthropic/claude-3-opus'); ?>>Claude 3 Opus</option>
                                                    <option value="anthropic/claude-3-sonnet" <?php selected($global_settings['global_openrouter_model_1'] ?? '', 'anthropic/claude-3-sonnet'); ?>>Claude 3 Sonnet</option>
                                                    <option value="anthropic/claude-3-haiku" <?php selected($global_settings['global_openrouter_model_1'] ?? '', 'anthropic/claude-3-haiku'); ?>>Claude 3 Haiku</option>
                                                </optgroup>
                                                <optgroup label="<?php esc_html_e('Meta', 'suggester'); ?>">
                                                    <option value="meta-llama/llama-2-70b-chat" <?php selected($global_settings['global_openrouter_model_1'] ?? '', 'meta-llama/llama-2-70b-chat'); ?>>Llama 2 70B</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                        <?php
                                        // Show status indicators and tracking for this key
                                        $last_key_index = get_transient('suggester_last_key_index_global');
                                        $key_error = get_transient('suggester_key_error_global_openrouter_1');
                                        
                                        if ($last_key_index == 1) {
                                            echo '<span class="suggester-key-status suggester-key-last-used">' . esc_html__('Last used', 'suggester') . '</span>';
                                        }
                                        
                                        if ($key_error) {
                                            echo '<span class="suggester-key-status suggester-key-error">' . esc_html__('Error: ', 'suggester') . esc_html(substr($key_error, 0, 50)) . (strlen($key_error) > 50 ? '...' : '') . '</span>';
                                        }
                                        
                                        // Get tracking statistics
                                        $suggester = Suggester::get_instance();
                                        $stats = $suggester->get_key_stats('global', 'openrouter', 1);
                                        
                                        if ($stats['uses'] > 0 || $stats['failures'] > 0) {
                                            $last_use = $stats['last_use'] ? human_time_diff(strtotime($stats['last_use'])) . ' ago' : 'Never';
                                            echo '<div class="suggester-key-tracking">';
                                            echo '<div class="suggester-tracking-info">';
                                            echo '<strong>' . esc_html__('Tracking:', 'suggester') . '</strong> ';
                                            echo sprintf(
                                                /* translators: %1$d: number of API uses, %2$d: total tokens used, %3$d: number of failures, %4$s: last use time */
                                                esc_html__('Uses: %1$d | Tokens: %2$d | Failures: %3$d | Last Use: %4$s', 'suggester'),
                                                esc_html($stats['uses']),
                                                esc_html($stats['total_tokens']),
                                                esc_html($stats['failures']),
                                                esc_html($last_use)
                                            );
                                            echo '</div>';
                                            echo '<button type="button" class="button button-small suggester-reset-stats" ';
                                            echo 'data-tool-id="global" ';
                                            echo 'data-api-type="openrouter" ';
                                            echo 'data-key-index="1" ';
                                            echo 'data-nonce="' . esc_attr(wp_create_nonce('suggester_reset_stats_global_openrouter_1')) . '" ';
                                            echo 'title="' . esc_attr__('Reset tracking statistics', 'suggester') . '">';
                                            echo '<span class="dashicons dashicons-update-alt"></span>';
                                            echo '</button>';
                                            echo '</div>';
                                        }
                                        ?>
                                        <p class="description">
                                            <?php esc_html_e('Enter your OpenRouter API key and select a model. Get a key at https://openrouter.ai', 'suggester'); ?><br>
                                            <?php esc_html_e('Leave this field empty to disable this key globally.', 'suggester'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="suggester-global-openrouter-key-2">
                                            <?php esc_html_e('OpenRouter API Key 2', 'suggester'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <div class="suggester-api-key-field">
                                            <input type="password" id="suggester-global-openrouter-key-2" 
                                                   name="global_openrouter_key_2" 
                                                   value="<?php echo esc_attr($global_settings['global_openrouter_key_2'] ?? ''); ?>"
                                                   class="regular-text suggester-key-input">
                                            <button type="button" class="button suggester-toggle-visibility">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </button>
                                            
                                            <select name="global_openrouter_model_2" class="suggester-model-select">
                                                <option value=""><?php esc_html_e('Select Model', 'suggester'); ?></option>
                                                <optgroup label="<?php esc_html_e('OpenAI', 'suggester'); ?>">
                                                    <option value="openai/gpt-4o" <?php selected($global_settings['global_openrouter_model_2'] ?? '', 'openai/gpt-4o'); ?>>GPT-4o</option>
                                                    <option value="openai/gpt-4o-mini" <?php selected($global_settings['global_openrouter_model_2'] ?? '', 'openai/gpt-4o-mini'); ?>>GPT-4o Mini</option>
                                                    <option value="openai/gpt-4" <?php selected($global_settings['global_openrouter_model_2'] ?? '', 'openai/gpt-4'); ?>>GPT-4</option>
                                                    <option value="openai/gpt-3.5-turbo" <?php selected($global_settings['global_openrouter_model_2'] ?? '', 'openai/gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                                </optgroup>
                                                <optgroup label="<?php esc_html_e('Anthropic', 'suggester'); ?>">
                                                    <option value="anthropic/claude-opus-4" <?php selected($global_settings['global_openrouter_model_2'] ?? '', 'anthropic/claude-opus-4'); ?>>Claude Opus 4</option>
                                                    <option value="anthropic/claude-sonnet-4" <?php selected($global_settings['global_openrouter_model_2'] ?? '', 'anthropic/claude-sonnet-4'); ?>>Claude Sonnet 4</option>
                                                    <option value="anthropic/claude-3-opus" <?php selected($global_settings['global_openrouter_model_2'] ?? '', 'anthropic/claude-3-opus'); ?>>Claude 3 Opus</option>
                                                                                                         <option value="anthropic/claude-3-sonnet" <?php selected($global_settings['global_openrouter_model_2'] ?? '', 'anthropic/claude-3-sonnet'); ?>>Claude 3 Sonnet</option>
                                                    <option value="anthropic/claude-3-haiku" <?php selected($global_settings['global_openrouter_model_2'] ?? '', 'anthropic/claude-3-haiku'); ?>>Claude 3 Haiku</option>
                                                </optgroup>
                                                <optgroup label="<?php esc_html_e('Meta', 'suggester'); ?>">
                                                    <option value="meta-llama/llama-2-70b-chat" <?php selected($global_settings['global_openrouter_model_2'] ?? '', 'meta-llama/llama-2-70b-chat'); ?>>Llama 2 70B</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                        <?php
                                        // Show status indicators and tracking for this key
                                        $last_key_index = get_transient('suggester_last_key_index_global');
                                        $key_error = get_transient('suggester_key_error_global_openrouter_2');
                                        
                                        if ($last_key_index == 2) {
                                            echo '<span class="suggester-key-status suggester-key-last-used">' . esc_html__('Last used', 'suggester') . '</span>';
                                        }
                                        
                                        if ($key_error) {
                                            echo '<span class="suggester-key-status suggester-key-error">' . esc_html__('Error: ', 'suggester') . esc_html(substr($key_error, 0, 50)) . (strlen($key_error) > 50 ? '...' : '') . '</span>';
                                        }
                                        
                                        // Get tracking statistics
                                        $suggester = Suggester::get_instance();
                                        $stats = $suggester->get_key_stats('global', 'openrouter', 2);
                                        
                                        if ($stats['uses'] > 0 || $stats['failures'] > 0) {
                                            $last_use = $stats['last_use'] ? human_time_diff(strtotime($stats['last_use'])) . ' ago' : 'Never';
                                            echo '<div class="suggester-key-tracking">';
                                            echo '<div class="suggester-tracking-info">';
                                            echo '<strong>' . esc_html__('Tracking:', 'suggester') . '</strong> ';
                                            echo sprintf(
                                                /* translators: %1$d: number of API uses, %2$d: total tokens used, %3$d: number of failures, %4$s: last use time */
                                                esc_html__('Uses: %1$d | Tokens: %2$d | Failures: %3$d | Last Use: %4$s', 'suggester'),
                                                esc_html($stats['uses']),
                                                esc_html($stats['total_tokens']),
                                                esc_html($stats['failures']),
                                                esc_html($last_use)
                                            );
                                            echo '</div>';
                                            echo '<button type="button" class="button button-small suggester-reset-stats" ';
                                            echo 'data-tool-id="global" ';
                                            echo 'data-api-type="openrouter" ';
                                            echo 'data-key-index="2" ';
                                            echo 'data-nonce="' . esc_attr(wp_create_nonce('suggester_reset_stats_global_openrouter_2')) . '" ';
                                            echo 'title="' . esc_attr__('Reset tracking statistics', 'suggester') . '">';
                                            echo '<span class="dashicons dashicons-update-alt"></span>';
                                            echo '</button>';
                                            echo '</div>';
                                        }
                                        ?>
                                        <p class="description">
                                            <?php esc_html_e('Enter your second OpenRouter API key and select a model. Multiple keys enable intelligent load balancing.', 'suggester'); ?><br>
                                            <?php esc_html_e('Leave this field empty to disable this key globally.', 'suggester'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Key Rotation Explanation Section -->
                    <div class="suggester-settings-section">
                        <div class="suggester-section-header">
                            <h2><?php esc_html_e('How Key Rotation Works', 'suggester'); ?></h2>
                            <p class="description">
                                <?php esc_html_e('Understanding the intelligent key rotation system and its benefits.', 'suggester'); ?>
                            </p>
                        </div>
                        
                        <div class="suggester-section-content">
                            <div class="suggester-key-rotation-explanation">
                                <div class="suggester-explanation-card">
                                    <div class="suggester-card-icon">
                                        <span class="dashicons dashicons-update-alt"></span>
                                    </div>
                                    <div class="suggester-card-content">
                                        <h3><?php esc_html_e('What is Key Rotation?', 'suggester'); ?></h3>
                                        <p><?php esc_html_e('When users generate suggestions, the plugin automatically switches between your API keys. This prevents overusing any single key and helps avoid rate limits.', 'suggester'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="suggester-explanation-card">
                                    <div class="suggester-card-icon">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                    </div>
                                    <div class="suggester-card-content">
                                        <h3><?php esc_html_e('Works with One Key Too', 'suggester'); ?></h3>
                                        <p><?php esc_html_e('You can use just one API key and the plugin will work perfectly. Adding more keys simply provides better performance and reliability.', 'suggester'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="suggester-explanation-card">
                                    <div class="suggester-card-icon">
                                        <span class="dashicons dashicons-performance"></span>
                                    </div>
                                    <div class="suggester-card-content">
                                        <h3><?php esc_html_e('Why Use Multiple Keys?', 'suggester'); ?></h3>
                                        <p><?php esc_html_e('Multiple keys reduce the load on each individual key. This is especially helpful if you expect high traffic or many suggestion requests.', 'suggester'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="suggester-explanation-card">
                                    <div class="suggester-card-icon">
                                        <span class="dashicons dashicons-admin-tools"></span>
                                    </div>
                                    <div class="suggester-card-content">
                                        <h3><?php esc_html_e('Global vs Tool-Specific Keys', 'suggester'); ?></h3>
                                        <p><?php esc_html_e('Keys configured here are used by all tools by default. For high-traffic tools, you can assign dedicated keys in each tool\'s individual settings for better performance.', 'suggester'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="suggester-recommendation-box">
                                    <div class="suggester-recommendation-icon">
                                        <span class="dashicons dashicons-lightbulb"></span>
                                    </div>
                                    <div class="suggester-recommendation-content">
                                        <h4><?php esc_html_e('Recommendation', 'suggester'); ?></h4>
                                        <ul>
                                            <li><?php esc_html_e('Start with at least one key to get up and running', 'suggester'); ?></li>
                                            <li><?php esc_html_e('Add 2-3 keys for better performance and reliability', 'suggester'); ?></li>
                                            <li><?php esc_html_e('Use tool-specific keys only for high-traffic tools', 'suggester'); ?></li>
                                            <li><?php esc_html_e('Monitor the tracking statistics to see how your keys are performing', 'suggester'); ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Button -->
                    <div class="suggester-settings-save">
                        <button type="submit" class="button button-primary button-large">
                            <?php esc_html_e('Save Settings', 'suggester'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Password visibility toggle
                const visibilityToggles = document.querySelectorAll('.suggester-toggle-visibility');
                visibilityToggles.forEach(function(toggle) {
                    toggle.addEventListener('click', function() {
                        const input = this.parentNode.querySelector('.suggester-key-input');
                        const icon = this.querySelector('.dashicons');
                        
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('dashicons-visibility');
                            icon.classList.add('dashicons-hidden');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('dashicons-hidden');
                            icon.classList.add('dashicons-visibility');
                        }
                    });
                });
                
                // Reset statistics functionality
                const resetButtons = document.querySelectorAll('.suggester-reset-stats');
                resetButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        if (!confirm('<?php echo esc_js(__('Are you sure you want to reset the tracking statistics for this API key? This action cannot be undone.', 'suggester')); ?>')) {
                            return;
                        }
                        
                        const toolId = this.getAttribute('data-tool-id');
                        const apiType = this.getAttribute('data-api-type');
                        const keyIndex = this.getAttribute('data-key-index');
                        const nonce = this.getAttribute('data-nonce');
                        
                        // Show loading state
                        this.disabled = true;
                        const originalHtml = this.innerHTML;
                        this.innerHTML = '<span class="dashicons dashicons-update-alt suggester-spin"></span>';
                        
                        // Send AJAX request
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', suggester_ajax.ajax_url, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        
                        xhr.onload = function() {
                            // Remove loading state
                            button.disabled = false;
                            button.innerHTML = originalHtml;
                            
                            if (xhr.status >= 200 && xhr.status < 300) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.success) {
                                        // Reload the page to show updated statistics
                                        window.location.reload();
                                    } else {
                                        alert(response.data || 'Failed to reset statistics.');
                                    }
                                } catch (e) {
                                    alert('Invalid response from server.');
                                }
                            } else {
                                alert('Request failed.');
                            }
                        };
                        
                        xhr.onerror = function() {
                            button.disabled = false;
                            button.innerHTML = originalHtml;
                            alert('Request failed.');
                        };
                        
                        // Prepare and send data
                        const params = 
                            'action=suggester_reset_key_stats' + 
                            '&tool_id=' + encodeURIComponent(toolId) + 
                            '&api_type=' + encodeURIComponent(apiType) + 
                            '&key_index=' + encodeURIComponent(keyIndex) + 
                            '&nonce=' + encodeURIComponent(nonce);
                        
                        xhr.send(params);
                    });
                });
            });
        </script>
        
        <style>
            .suggester-settings-container {
                max-width: 1000px;
                margin-top: 20px;
            }
            
            .suggester-settings-section {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
                margin-bottom: 20px;
            }
            
            .suggester-section-header {
                padding: 20px;
                border-bottom: 1px solid #eee;
            }
            
            .suggester-section-header h2 {
                margin: 0 0 10px 0;
                font-size: 20px;
            }
            
            .suggester-section-header .description {
                margin: 0;
                color: #666;
                font-size: 14px;
                line-height: 1.5;
            }
            
            .suggester-section-content {
                padding: 20px;
            }
            
            .suggester-settings-save {
                padding: 20px 0;
            }
            
            /* API Key fields styling */
            .suggester-api-key-field {
                display: flex;
                align-items: center;
                gap: 5px;
                margin-bottom: 5px;
            }
            
            .suggester-key-input {
                flex: 1;
            }
            
            .suggester-toggle-visibility {
                padding: 0 5px;
            }
            
            .suggester-toggle-visibility .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                line-height: 1.3;
            }
            
            .suggester-model-select {
                min-width: 180px;
            }
            
            /* API Key Status Indicators */
            .suggester-key-status {
                display: inline-block;
                margin-top: 5px;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: normal;
            }
            
            .suggester-key-last-used {
                background-color: #e7f5ea;
                color: #0a9b47;
                border: 1px solid #0a9b47;
            }
            
            .suggester-key-error {
                background-color: #ffeaea;
                color: #d63638;
                border: 1px solid #d63638;
            }
            
            /* API Key Tracking Display */
            .suggester-key-tracking {
                margin-top: 8px;
                padding: 8px 12px;
                background-color: #f8f9fa;
                border: 1px solid #e1e5e9;
                border-radius: 4px;
                font-size: 13px;
                color: #495057;
                line-height: 1.4;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }
            
            .suggester-tracking-info {
                flex: 1;
            }
            
            .suggester-key-tracking strong {
                color: #2c3e50;
                font-weight: 600;
            }
            
            .suggester-reset-stats {
                padding: 2px 6px !important;
                min-height: auto !important;
                line-height: 1 !important;
                border-radius: 3px;
                background-color: #f0f0f1 !important;
                border-color: #c3c4c7 !important;
                color: #3c434a !important;
                flex-shrink: 0;
            }
            
            .suggester-reset-stats:hover {
                background-color: #e9e9ea !important;
                border-color: #8c8f94 !important;
            }
            
            .suggester-reset-stats .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
                line-height: 1;
            }
            
            /* Loading spinner animation */
            .suggester-spin {
                animation: suggester-spin 1s linear infinite;
            }
            
            @keyframes suggester-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            /* Key Rotation Explanation Styles */
            .suggester-key-rotation-explanation {
                max-width: 800px;
            }
            
            .suggester-explanation-card {
                display: flex;
                align-items: flex-start;
                gap: 16px;
                padding: 20px;
                margin-bottom: 16px;
                background: #f8f9fa;
                border: 1px solid #e1e5e9;
                border-radius: 8px;
                transition: all 0.2s ease;
            }
            
            .suggester-explanation-card:hover {
                background: #f1f3f4;
                border-color: #2271b1;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            
            .suggester-card-icon {
                flex-shrink: 0;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #2271b1;
                border-radius: 50%;
                color: #fff;
            }
            
            .suggester-card-icon .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
            
            .suggester-card-content {
                flex: 1;
            }
            
            .suggester-card-content h3 {
                margin: 0 0 8px 0;
                font-size: 16px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .suggester-card-content p {
                margin: 0;
                color: #50575e;
                line-height: 1.5;
                font-size: 14px;
            }
            
            .suggester-recommendation-box {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 8px;
                padding: 20px;
                margin-top: 24px;
                display: flex;
                align-items: flex-start;
                gap: 16px;
            }
            
            .suggester-recommendation-icon {
                flex-shrink: 0;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #856404;
                border-radius: 50%;
                color: #fff;
            }
            
            .suggester-recommendation-icon .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
            
            .suggester-recommendation-content {
                flex: 1;
            }
            
            .suggester-recommendation-content h4 {
                margin: 0 0 12px 0;
                font-size: 16px;
                font-weight: 600;
                color: #856404;
            }
            
            .suggester-recommendation-content ul {
                margin: 0;
                padding-left: 20px;
                color: #856404;
            }
            
            .suggester-recommendation-content li {
                margin-bottom: 4px;
                line-height: 1.5;
                font-size: 14px;
            }
            
            /* Responsive adjustments for explanation cards */
            @media (max-width: 768px) {
                .suggester-explanation-card {
                    flex-direction: column;
                    text-align: center;
                    gap: 12px;
                }
                
                .suggester-recommendation-box {
                    flex-direction: column;
                    text-align: center;
                    gap: 12px;
                }
                
                .suggester-key-rotation-explanation {
                    max-width: 100%;
                }
            }
        </style>
        <?php
    }
} 