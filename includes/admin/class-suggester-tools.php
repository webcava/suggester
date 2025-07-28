<?php
/**
 * Suggester Tools Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tools Class
 */
class Suggester_Tools {
    
    /**
     * Maximum number of tools allowed in free version
     */
    const MAX_TOOLS = 3;
    
    /**
     * Constructor
     */
    

    
    /**
     * Current view
     */
    private $current_view = 'list';
    
    /**
     * Current tool ID being edited
     */
    private $current_tool_id = 0;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->process_actions();
        $this->process_form_submissions();
    }
    
    /**
     * Process form submissions
     */
    private function process_form_submissions() {
        // Check if this is a tool save
        $action = sanitize_text_field(wp_unslash($_POST['action'] ?? ''));
        if (isset($_POST['action']) && $action === 'save_tool' && isset($_POST['tool_id'])) {
            $tool_id = absint($_POST['tool_id']);
            
            $is_new_tool = ($tool_id === 0);
            
            // If tool_id is 0, this is a new tool creation
            if ($is_new_tool) {
                // Check if we can create more tools
                $tool_count = $this->get_tool_count();
                if ($tool_count >= self::MAX_TOOLS) {
                    // Store error message
                    set_transient('suggester_admin_message', array(
                        'type' => 'error',
                        'message' => __('Maximum number of tools reached. Cannot create new tool.', 'suggester')
                    ), 60);
                    return;
                }
                
                // Create the new tool
                $tool_id = $this->create_new_tool();
            }
            
            // Verify nonce (use the original tool_id for nonce verification)
            $nonce_tool_id = $is_new_tool ? 0 : $tool_id;
            if (isset($_POST['suggester_tool_nonce']) && wp_verify_nonce(sanitize_key($_POST['suggester_tool_nonce']), 'suggester_save_tool_' . $nonce_tool_id)) {
                // Get existing settings
                $tool_settings = get_option('suggester_tool_settings_' . $tool_id, array());
                
                // Update name if provided
                if (isset($_POST['name'])) {
                    $tool_settings['name'] = sanitize_text_field($_POST['name']);
                }
                
                // Update template if provided
                if (isset($_POST['template'])) {
                    $tool_settings['template'] = sanitize_text_field($_POST['template']);
                }
                
                // Update custom API keys setting
                $tool_settings['custom_api_keys'] = isset($_POST['custom_api_keys']) ? true : false;
                
                // Update API keys if custom keys are enabled
                if ($tool_settings['custom_api_keys']) {
                    // Sanitize Gemini key
                    if (isset($_POST['gemini_key'])) {
                        $tool_settings['gemini_key'] = sanitize_text_field($_POST['gemini_key']);
                    }
                    
                    // Sanitize OpenRouter keys and models
                    if (isset($_POST['openrouter_key_1'])) {
                        $tool_settings['openrouter_key_1'] = sanitize_text_field($_POST['openrouter_key_1']);
                    }
                    
                    if (isset($_POST['openrouter_model_1'])) {
                        $tool_settings['openrouter_model_1'] = sanitize_text_field($_POST['openrouter_model_1']);
                    }
                    
                    if (isset($_POST['openrouter_key_2'])) {
                        $tool_settings['openrouter_key_2'] = sanitize_text_field($_POST['openrouter_key_2']);
                    }
                    
                    if (isset($_POST['openrouter_model_2'])) {
                        $tool_settings['openrouter_model_2'] = sanitize_text_field($_POST['openrouter_model_2']);
                    }
                }
                
                // Update prompt template
                if (isset($_POST['prompt_template'])) {
                    $tool_settings['prompt_template'] = sanitize_textarea_field($_POST['prompt_template']);
                }
                
                // Update default suggestion count
                if (isset($_POST['default_count'])) {
                    $tool_settings['default_count'] = absint($_POST['default_count']);
                    // Ensure count is between 1 and 10
                    $tool_settings['default_count'] = max(1, min(10, $tool_settings['default_count']));
                }
                
                // Update template config if provided
                if (isset($_POST['template_config']) && is_array($_POST['template_config'])) {
                    $template_config = array();
                    
                    // Sanitize accent color
                    if (isset($_POST['template_config']['accent_color'])) {
                        $template_config['accent_color'] = sanitize_hex_color($_POST['template_config']['accent_color']);
                    }
                    
                    // Sanitize button text color
                    if (isset($_POST['template_config']['button_text_color'])) {
                        $template_config['button_text_color'] = sanitize_hex_color($_POST['template_config']['button_text_color']);
                    }
                    
                    // Sanitize show favorites
                    $template_config['show_favorites'] = isset($_POST['template_config']['show_favorites']) ? true : false;
                    
                    // Update template config
                    $tool_settings['template_config'] = $template_config;
                }
                
                // Update modified timestamp
                $tool_settings['modified'] = current_time('mysql');
                
                // Save settings
                update_option('suggester_tool_settings_' . $tool_id, $tool_settings);
                
                // Store success message in transient
                set_transient('suggester_admin_message', array(
                    'type' => 'success',
                    'message' => __('Tool settings saved successfully.', 'suggester')
                ), 60);
                
                // Use JavaScript redirect instead of wp_redirect to avoid headers issue
                add_action('admin_footer', function() use ($tool_id, $is_new_tool) {
                    $tab = isset($_POST['current_tab']) ? sanitize_key($_POST['current_tab']) : 'basics';
                    $redirect_url = add_query_arg(
                        array('action' => 'edit', 'tool_id' => $tool_id, 'tab' => $tab, '_success' => '1'),
                        admin_url('admin.php?page=suggester-tools')
                    );
                    echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
                });
            }
        }
    }
    
    /**
     * Process tool actions (create, edit, delete)
     */
    private function process_actions() {
        // Get current action
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        $tool_id = isset($_GET['tool_id']) ? absint($_GET['tool_id']) : 0;
        
        // Process actions
        switch ($action) {
            case 'edit':
                if ($tool_id > 0) {
                    $this->current_view = 'edit';
                    $this->current_tool_id = $tool_id;
                }
                break;
                
            case 'create':
                // Check if we can create more tools
                $tool_count = $this->get_tool_count();
                if ($tool_count < self::MAX_TOOLS) {
                    $this->current_view = 'edit';
                    $this->current_tool_id = 0; // Set to 0 to indicate new tool
                }
                break;
                
            case 'delete':
                // Verify nonce for security
                if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'suggester_delete_tool_' . $tool_id)) {
                    $this->delete_tool($tool_id);
                    
                    // Store success message in transient
                    set_transient('suggester_admin_message', array(
                        'type' => 'success',
                        'message' => __('Tool deleted successfully.', 'suggester')
                    ), 60);
                    
                    // JavaScript redirect
                    add_action('admin_footer', function() {
                        $redirect_url = add_query_arg(
                            array('_success' => '1'),
                            admin_url('admin.php?page=suggester-tools')
                        );
                        echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
                    });
                }
                break;
        }
    }
    
    /**
     * Get count of existing tools
     */
    private function get_tool_count() {
        $tool_counter = get_option('suggester_tool_counter', 0);
        $actual_count = 0;
        
        // Count existing tools
        for ($i = 1; $i <= $tool_counter; $i++) {
            if (get_option('suggester_tool_settings_' . $i)) {
                $actual_count++;
            }
        }
        
        return $actual_count;
    }
    
    /**
     * Create new tool and return its ID
     */
    private function create_new_tool() {
        // Get current tool counter
        $tool_counter = get_option('suggester_tool_counter', 0);
        
        // Increment counter
        $tool_counter++;
        update_option('suggester_tool_counter', $tool_counter);
        
        // Create default settings for the new tool
        $default_settings = array(
            /* translators: %1$d: tool counter number */
            'name' => sprintf(esc_html__('New Tool %1$d', 'suggester'), $tool_counter),
            'active' => true,
            'template' => 'night-mode',
            'custom_api_keys' => false,
            'prompt_template' => 'Generate {count} creative suggestions for {keyword} in {language}. Each suggestion should be on a separate line and be concise (maximum 10 words each).',
            'default_count' => 3,
            'template_config' => array(
                'accent_color' => '#bb86fc',
                'button_text_color' => '#ffffff',
                'show_favorites' => true
            ),
            'created' => current_time('mysql'),
            'modified' => current_time('mysql')
        );
        
        // Save default settings
        update_option('suggester_tool_settings_' . $tool_counter, $default_settings);
        
        return $tool_counter;
    }
    
    /**
     * Delete a tool
     */
    private function delete_tool($tool_id) {
        if ($tool_id > 0) {
            delete_option('suggester_tool_settings_' . $tool_id);
            return true;
        }
        return false;
    }
    
    /**
     * Get all tools
     */
    private function get_tools() {
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
    
    /**
     * Render the tools page
     */
    public function render() {
        $this->render_headers();
        
        if ($this->current_view === 'edit') {
            $this->render_edit_view();
        } else {
            $this->render_list_view();
        }
    }
    
    /**
     * Render the tools list view
     */
    private function render_list_view() {
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
        
        $tools = $this->get_tools();
        $tool_count = count($tools);
        $max_reached = $tool_count >= self::MAX_TOOLS;
        
        ?>
        <div class="wrap suggester-admin-page">
            <h1><?php esc_html_e('Tools', 'suggester'); ?></h1>
            
            <div class="suggester-create-new-tool">
                <?php if (!$max_reached) : ?>
                <a href="<?php echo esc_url(add_query_arg(array('action' => 'create'), admin_url('admin.php?page=suggester-tools'))); ?>" class="button button-primary">
                    <?php esc_html_e('Create New Tool', 'suggester'); ?>
                </a>
                <?php else : ?>
                <button class="button button-primary" disabled="disabled" title="<?php esc_attr_e('You are using the free version and cannot create more than three tools.', 'suggester'); ?>">
                    <?php esc_html_e('Create New Tool', 'suggester'); ?>
                </button>
                <span class="suggester-max-tools-notice">
                    <?php esc_html_e('Maximum tool limit reached.', 'suggester'); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <div class="suggester-tools-table-container">
                <table class="wp-list-table widefat fixed striped suggester-tools-table">
                    <thead>
                        <tr>
                            <th class="column-name"><?php esc_html_e('Tool Name', 'suggester'); ?></th>
                            <th class="column-shortcode"><?php esc_html_e('Shortcode', 'suggester'); ?></th>
                            <th class="column-status"><?php esc_html_e('Status', 'suggester'); ?></th>
                            <th class="column-actions"><?php esc_html_e('Actions', 'suggester'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tool_count > 0) : ?>
                            <?php foreach ($tools as $tool_id => $tool) : ?>
                                <tr id="suggester-tool-<?php echo esc_attr($tool_id); ?>">
                                    <td class="column-name">
                                        <?php echo esc_html($tool['name']); ?>
                                    </td>
                                    <td class="column-shortcode">
                                        <div class="suggester-shortcode-container">
                                            <code>[suggester id='<?php echo esc_attr($tool_id); ?>']</code>
                                            <button type="button" class="suggester-copy-shortcode button button-small" 
                                                    data-shortcode="[suggester id='<?php echo esc_attr($tool_id); ?>']">
                                                <span class="dashicons dashicons-clipboard"></span>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="column-status">
                                        <label class="suggester-switch">
                                            <input type="checkbox" class="suggester-tool-status" 
                                                   data-tool-id="<?php echo esc_attr($tool_id); ?>"
                                                   data-nonce="<?php echo esc_attr(wp_create_nonce('suggester_toggle_tool_' . $tool_id)); ?>"
                                                   <?php checked($tool['active'], true); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td class="column-actions">
                                        <div class="suggester-tool-actions">
                                            <a href="<?php echo esc_url(add_query_arg(array('action' => 'edit', 'tool_id' => $tool_id), admin_url('admin.php?page=suggester-tools'))); ?>" 
                                               class="button button-small" title="<?php esc_attr_e('Edit Tool', 'suggester'); ?>">
                                                <span class="dashicons dashicons-edit"></span>
                                            </a>
                                            
                                            <?php if ($tool_count < self::MAX_TOOLS) : ?>
                                            <a href="#" class="button button-small suggester-duplicate-tool" 
                                               data-tool-id="<?php echo esc_attr($tool_id); ?>"
                                               data-nonce="<?php echo esc_attr(wp_create_nonce('suggester_duplicate_tool_' . $tool_id)); ?>"
                                               title="<?php esc_attr_e('Duplicate Tool', 'suggester'); ?>">
                                                <span class="dashicons dashicons-admin-page"></span>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'delete', 'tool_id' => $tool_id), admin_url('admin.php?page=suggester-tools')), 'suggester_delete_tool_' . $tool_id)); ?>" 
                                               class="button button-small suggester-delete-tool" 
                                               data-tool-id="<?php echo esc_attr($tool_id); ?>"
                                               title="<?php esc_attr_e('Delete Tool', 'suggester'); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" class="suggester-no-tools">
                                    <?php esc_html_e('No tools found. Create your first tool to get started.', 'suggester'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Copy shortcode functionality
                const copyButtons = document.querySelectorAll('.suggester-copy-shortcode');
                copyButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        const shortcode = this.getAttribute('data-shortcode');
                        navigator.clipboard.writeText(shortcode).then(function() {
                            // Change button text temporarily to indicate success
                            const originalHtml = button.innerHTML;
                            button.innerHTML = '<span class="dashicons dashicons-yes"></span>';
                            setTimeout(function() {
                                button.innerHTML = originalHtml;
                            }, 1000);
                        });
                    });
                });
                
                // Tool status toggle
                const statusToggles = document.querySelectorAll('.suggester-tool-status');
                statusToggles.forEach(function(toggle) {
                    toggle.addEventListener('change', function() {
                        const toolId = this.getAttribute('data-tool-id');
                        const nonce = this.getAttribute('data-nonce');
                        const isActive = this.checked;
                        
                        // Show loading state
                        this.disabled = true;
                        const row = document.getElementById('suggester-tool-' + toolId);
                        if (row) {
                            row.classList.add('suggester-loading');
                        }
                        
                        // Send AJAX request
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', suggester_ajax.ajax_url, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        
                        xhr.onload = function() {
                            // Remove loading state
                            toggle.disabled = false;
                            if (row) {
                                row.classList.remove('suggester-loading');
                            }
                            
                            if (xhr.status >= 200 && xhr.status < 300) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    if (!response.success) {
                                        // Revert toggle if failed
                                        toggle.checked = !isActive;
                                        alert(response.data || 'Failed to update tool status.');
                                    }
                                } catch (e) {
                                    // Revert toggle if error
                                    toggle.checked = !isActive;
                                    alert('Invalid response from server.');
                                }
                            } else {
                                // Revert toggle if request failed
                                toggle.checked = !isActive;
                                alert('Request failed.');
                            }
                        };
                        
                        xhr.onerror = function() {
                            // Revert toggle if error
                            toggle.disabled = false;
                            toggle.checked = !isActive;
                            if (row) {
                                row.classList.remove('suggester-loading');
                            }
                            alert('Request failed.');
                        };
                        
                        // Prepare and send data
                        const params = 
                            'action=suggester_toggle_tool_status' + 
                            '&tool_id=' + encodeURIComponent(toolId) + 
                            '&active=' + (isActive ? '1' : '0') + 
                            '&nonce=' + encodeURIComponent(nonce);
                        
                        xhr.send(params);
                    });
                });
                
                // Delete confirmation
                const deleteButtons = document.querySelectorAll('.suggester-delete-tool');
                deleteButtons.forEach(function(button) {
                    button.addEventListener('click', function(e) {
                        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this tool? This action cannot be undone.', 'suggester')); ?>')) {
                            e.preventDefault();
                        }
                    });
                });
                
                // Duplicate tool
                const duplicateButtons = document.querySelectorAll('.suggester-duplicate-tool');
                duplicateButtons.forEach(function(button) {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        const toolId = this.getAttribute('data-tool-id');
                        const nonce = this.getAttribute('data-nonce');
                        
                        // Show loading state
                        this.disabled = true;
                        const row = document.getElementById('suggester-tool-' + toolId);
                        if (row) {
                            row.classList.add('suggester-loading');
                        }
                        
                        // Send AJAX request
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', suggester_ajax.ajax_url, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        
                        xhr.onload = function() {
                            // Remove loading state
                            button.disabled = false;
                            if (row) {
                                row.classList.remove('suggester-loading');
                            }
                            
                            if (xhr.status >= 200 && xhr.status < 300) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.success) {
                                        // Reload page to show the new tool
                                        window.location.reload();
                                    } else {
                                        alert(response.data || 'Failed to duplicate tool.');
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
                            if (row) {
                                row.classList.remove('suggester-loading');
                            }
                            alert('Request failed.');
                        };
                        
                        // Prepare and send data
                        const params = 
                            'action=suggester_duplicate_tool' + 
                            '&tool_id=' + encodeURIComponent(toolId) + 
                            '&nonce=' + encodeURIComponent(nonce);
                        
                        xhr.send(params);
                    });
                });
            });
        </script>
        
        <style>
            .suggester-create-new-tool {
                margin: 20px 0;
            }
            
            .suggester-max-tools-notice {
                display: inline-block;
                margin-left: 10px;
                color: #dc3232;
                font-style: italic;
            }
            
            .suggester-tools-table {
                margin-top: 15px;
            }
            
            .suggester-tools-table .column-name {
                width: 30%;
            }
            
            .suggester-tools-table .column-shortcode {
                width: 30%;
            }
            
            .suggester-tools-table .column-status {
                width: 15%;
                text-align: center;
            }
            
            .suggester-tools-table .column-actions {
                width: 25%;
                text-align: center;
            }
            
            .suggester-shortcode-container {
                display: flex;
                align-items: center;
            }
            
            .suggester-shortcode-container code {
                margin-right: 5px;
                padding: 5px;
                background: #f6f7f7;
                border-radius: 3px;
            }
            
            .suggester-copy-shortcode {
                cursor: pointer;
            }
            
            .suggester-copy-shortcode .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                line-height: 1;
                margin-top: 3px;
            }
            
            .suggester-tool-actions {
                display: flex;
                justify-content: center;
                gap: 5px;
            }
            
            .suggester-tool-actions .button {
                padding: 0 5px;
            }
            
            .suggester-tool-actions .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                line-height: 1.3;
            }
            
            .suggester-delete-tool .dashicons {
                color: #cc0000;
            }
            
            .suggester-no-tools {
                text-align: center;
                padding: 20px;
                font-style: italic;
            }
            
            /* Switch styling */
            .suggester-switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            
            .suggester-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
            }
            
            .slider:before {
                position: absolute;
                content: "";
                height: 16px;
                width: 16px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
            }
            
            input:checked + .slider {
                background-color: #2196F3;
            }
            
            input:focus + .slider {
                box-shadow: 0 0 1px #2196F3;
            }
            
            input:checked + .slider:before {
                transform: translateX(26px);
            }
            
            .slider.round {
                border-radius: 24px;
            }
            
            .slider.round:before {
                border-radius: 50%;
            }
            
            /* Loading state */
            .suggester-loading {
                opacity: 0.6;
                pointer-events: none;
            }
        </style>
        <?php
    }
    
    /**
     * Render the tool edit view
     */
    private function render_edit_view() {
        // Get tool settings
        $tool_id = $this->current_tool_id;
        $tool_settings = get_option('suggester_tool_settings_' . $tool_id, array());
        
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
        
        // Current tab - get from URL, session storage or default to basics
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'basics';
        
        // Define tabs
        $tabs = array(
            'basics' => __('Basics', 'suggester'),
            'templates' => __('Templates', 'suggester'),
            'customization' => __('Customization', 'suggester')
        );
        
        ?>
        <div class="wrap suggester-admin-page">
            <h1>
                <?php 
                if (empty($tool_settings)) {
                    esc_html_e('Create New Tool', 'suggester');
                } else {
                    esc_html_e('Edit Tool', 'suggester'); 
                    echo ': ' . esc_html($tool_settings['name'] ?? '');
                }
                ?>
            </h1>
            
            <div class="suggester-tool-edit-container">
                <div class="suggester-tool-header">
                    <div class="suggester-tool-info">
                        <?php if (!empty($tool_settings)) : ?>
                        <div class="suggester-tool-shortcode">
                            <span class="suggester-label"><?php esc_html_e('Shortcode:', 'suggester'); ?></span>
                            <code>[suggester id='<?php echo esc_attr($tool_id); ?>']</code>
                            <button type="button" class="suggester-copy-shortcode button button-small" 
                                    data-shortcode="[suggester id='<?php echo esc_attr($tool_id); ?>']">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="suggester-action-buttons">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=suggester-tools')); ?>" class="button">
                            <?php esc_html_e('Back to Tools', 'suggester'); ?>
                        </a>
                        <button type="submit" form="suggester-edit-form" class="button button-primary">
                            <?php esc_html_e('Save Changes', 'suggester'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="suggester-tabs-wrapper">
                    <nav class="nav-tab-wrapper suggester-nav-tab-wrapper">
                        <?php foreach ($tabs as $tab_key => $tab_name) : ?>
                            <a href="#<?php echo esc_attr($tab_key); ?>" 
                               class="nav-tab <?php echo ($current_tab === $tab_key) ? 'nav-tab-active' : ''; ?>"
                               data-tab="<?php echo esc_attr($tab_key); ?>">
                                <?php echo esc_html($tab_name); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                    
                    <form id="suggester-edit-form" method="post" action="">
                        <?php wp_nonce_field('suggester_save_tool_' . $tool_id, 'suggester_tool_nonce'); ?>
                        <input type="hidden" name="tool_id" value="<?php echo esc_attr($tool_id); ?>">
                        <input type="hidden" name="action" value="save_tool">
                        <input type="hidden" name="current_tab" id="suggester-current-tab" value="<?php echo esc_attr($current_tab); ?>">
                        
                        <div class="suggester-tab-content">
                            <!-- Basics Tab -->
                            <div id="basics-panel" class="suggester-tab-panel" 
                                 style="<?php echo esc_attr($current_tab === 'basics' ? 'display:block;' : 'display:none;'); ?>">
                                <h2><?php esc_html_e('Basic Settings', 'suggester'); ?></h2>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="suggester-tool-name">
                                                <?php esc_html_e('Tool Name', 'suggester'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" id="suggester-tool-name" 
                                                   name="name" 
                                                   value="<?php echo esc_attr($tool_settings['name'] ?? ''); ?>"
                                                   class="regular-text" required>
                                            <p class="description">
                                                <?php esc_html_e('Enter a name to identify this suggestion tool in the admin panel.', 'suggester'); ?>
                                            </p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="suggester-custom-api-keys">
                                                <?php esc_html_e('Custom API Keys', 'suggester'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <label class="suggester-switch">
                                                <input type="checkbox" id="suggester-custom-api-keys" 
                                                       name="custom_api_keys" 
                                                       value="1" <?php checked(isset($tool_settings['custom_api_keys']) ? $tool_settings['custom_api_keys'] : false, true); ?>>
                                                <span class="slider round"></span>
                                            </label>
                                            <span class="suggester-switch-label">
                                                <?php esc_html_e('Use custom API keys for this tool', 'suggester'); ?>
                                            </span>
                                            <p class="description">
                                                <?php esc_html_e('Enable to use specific API keys for this tool instead of global settings.', 'suggester'); ?>
                                            </p>
                                        </td>
                                    </tr>

                                    <tr class="suggester-global-keys-notice" style="<?php echo (isset($tool_settings['custom_api_keys']) && $tool_settings['custom_api_keys']) ? 'display:none;' : ''; ?>">
                                        <th scope="row"></th>
                                        <td>
                                            <div class="suggester-notice suggester-notice-info">
                                                <span class="dashicons dashicons-info"></span>
                                                <span class="suggester-notice-text">
                                                    <?php esc_html_e('This tool is using global API keys from the Settings page.', 'suggester'); ?>
                                                    <a href="<?php echo esc_url(admin_url('admin.php?page=suggester-settings')); ?>" target="_blank">
                                                        <?php esc_html_e('Manage Global Keys', 'suggester'); ?> 
                                                        <span class="dashicons dashicons-external"></span>
                                                    </a>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="suggester-api-keys-section" style="<?php echo (isset($tool_settings['custom_api_keys']) && $tool_settings['custom_api_keys']) ? '' : 'display:none;'; ?>">
                                        <th scope="row">
                                            <label for="suggester-gemini-key">
                                                <?php esc_html_e('Google Gemini API Key', 'suggester'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <div class="suggester-api-key-field">
                                                <input type="password" id="suggester-gemini-key" 
                                                       name="gemini_key" 
                                                       value="<?php echo esc_attr($tool_settings['gemini_key'] ?? ''); ?>"
                                                       class="regular-text suggester-key-input">
                                                <button type="button" class="button suggester-toggle-visibility">
                                                    <span class="dashicons dashicons-visibility"></span>
                                                </button>
                                            </div>
                                            <?php
                                            // Show status indicators and tracking for this key (only for existing tools)
                                            $tool_id = $this->current_tool_id;
                                            if ($tool_id > 0) {
                                                $last_key_index = get_transient('suggester_last_key_index_' . $tool_id);
                                                $key_error = get_transient('suggester_key_error_' . $tool_id . '_gemini_0');
                                                
                                                if ($last_key_index == 0) {
                                                    echo '<span class="suggester-key-status suggester-key-last-used">' . esc_html__('Last used', 'suggester') . '</span>';
                                                }
                                                
                                                if ($key_error) {
                                                    echo '<span class="suggester-key-status suggester-key-error">' . esc_html__('Error: ', 'suggester') . esc_html(substr($key_error, 0, 50)) . (strlen($key_error) > 50 ? '...' : '') . '</span>';
                                                }
                                                
                                                // Get tracking statistics
                                                $suggester = Suggester::get_instance();
                                                $stats = $suggester->get_key_stats($tool_id, 'gemini', 0);
                                                
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
                                                echo 'data-tool-id="' . esc_attr($tool_id) . '" ';
                                                echo 'data-api-type="gemini" ';
                                                echo 'data-key-index="0" ';
                                                echo 'data-nonce="' . esc_attr(wp_create_nonce('suggester_reset_stats_' . $tool_id . '_gemini_0')) . '" ';
                                                echo 'title="' . esc_attr__('Reset tracking statistics', 'suggester') . '">';
                                                echo '<span class="dashicons dashicons-update-alt"></span>';
                                                echo '</button>';
                                                echo '</div>';
                                            }
                                            }
                                            ?>
                                            <p class="description">
                                                <?php esc_html_e('Enter your Google Gemini API key. Get one at https://ai.google.dev/', 'suggester'); ?><br>
                                                <?php esc_html_e('Leave this field empty to disable this key.', 'suggester'); ?>
                                            </p>
                                        </td>
                                    </tr>

                                    <tr class="suggester-api-keys-section" style="<?php echo (isset($tool_settings['custom_api_keys']) && $tool_settings['custom_api_keys']) ? '' : 'display:none;'; ?>">
                                        <th scope="row">
                                            <label for="suggester-openrouter-key-1">
                                                <?php esc_html_e('OpenRouter API Key 1', 'suggester'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <div class="suggester-api-key-field">
                                                <input type="password" id="suggester-openrouter-key-1" 
                                                       name="openrouter_key_1" 
                                                       value="<?php echo esc_attr($tool_settings['openrouter_key_1'] ?? ''); ?>"
                                                       class="regular-text suggester-key-input">
                                                <button type="button" class="button suggester-toggle-visibility">
                                                    <span class="dashicons dashicons-visibility"></span>
                                                </button>
                                                
                                                <select name="openrouter_model_1" class="suggester-model-select">
                                                    <option value=""><?php esc_html_e('Select Model', 'suggester'); ?></option>
                                                    <optgroup label="<?php esc_html_e('OpenAI', 'suggester'); ?>">
                                                        <option value="openai/gpt-4o" <?php selected($tool_settings['openrouter_model_1'] ?? '', 'openai/gpt-4o'); ?>>GPT-4o</option>
                                                        <option value="openai/gpt-4o-mini" <?php selected($tool_settings['openrouter_model_1'] ?? '', 'openai/gpt-4o-mini'); ?>>GPT-4o Mini</option>
                                                        <option value="openai/gpt-4" <?php selected($tool_settings['openrouter_model_1'] ?? '', 'openai/gpt-4'); ?>>GPT-4</option>
                                                        <option value="openai/gpt-3.5-turbo" <?php selected($tool_settings['openrouter_model_1'] ?? '', 'openai/gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_html_e('Anthropic', 'suggester'); ?>">
                                                        <option value="anthropic/claude-opus-4" <?php selected($tool_settings['openrouter_model_1'] ?? '', 'anthropic/claude-opus-4'); ?>>Claude Opus 4</option>
                                                        <option value="anthropic/claude-sonnet-4" <?php selected($tool_settings['openrouter_model_1'] ?? '', 'anthropic/claude-sonnet-4'); ?>>Claude Sonnet 4</option>
                                                        <option value="anthropic/claude-3-opus" <?php selected($tool_settings['openrouter_model_1'] ?? '', 'anthropic/claude-3-opus'); ?>>Claude 3 Opus</option>
                                                        <option value="anthropic/claude-3-sonnet" <?php selected($tool_settings['openrouter_model_1'] ?? '', 'anthropic/claude-3-sonnet'); ?>>Claude 3 Sonnet</option>
                                                        <option value="anthropic/claude-3-haiku" <?php selected($tool_settings['openrouter_model_1'] ?? '', 'anthropic/claude-3-haiku'); ?>>Claude 3 Haiku</option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_html_e('Meta', 'suggester'); ?>">
                                                        <option value="meta-llama/llama-2-70b-chat" <?php selected($tool_settings['openrouter_model_1'] ?? '', 'meta-llama/llama-2-70b-chat'); ?>>Llama 2 70B</option>
                                                    </optgroup>
                                                </select>
                                            </div>
                                            <?php
                                            // Show status indicators and tracking for this key (only for existing tools)
                                            $tool_id = $this->current_tool_id;
                                            if ($tool_id > 0) {
                                                $last_key_index = get_transient('suggester_last_key_index_' . $tool_id);
                                                $key_error = get_transient('suggester_key_error_' . $tool_id . '_openrouter_1');
                                                
                                                if ($last_key_index == 1) {
                                                    echo '<span class="suggester-key-status suggester-key-last-used">' . esc_html__('Last used', 'suggester') . '</span>';
                                                }
                                                
                                                if ($key_error) {
                                                    echo '<span class="suggester-key-status suggester-key-error">' . esc_html__('Error: ', 'suggester') . esc_html(substr($key_error, 0, 50)) . (strlen($key_error) > 50 ? '...' : '') . '</span>';
                                                }
                                                
                                                // Get tracking statistics
                                                $suggester = Suggester::get_instance();
                                                $stats = $suggester->get_key_stats($tool_id, 'openrouter', 1);
                                                
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
                                                echo 'data-tool-id="' . esc_attr($tool_id) . '" ';
                                                echo 'data-api-type="openrouter" ';
                                                echo 'data-key-index="1" ';
                                                echo 'data-nonce="' . esc_attr(wp_create_nonce('suggester_reset_stats_' . $tool_id . '_openrouter_1')) . '" ';
                                                echo 'title="' . esc_attr__('Reset tracking statistics', 'suggester') . '">';
                                                echo '<span class="dashicons dashicons-update-alt"></span>';
                                                echo '</button>';
                                                echo '</div>';
                                            }
                                            }
                                            ?>
                                            <p class="description">
                                                <?php esc_html_e('Enter your OpenRouter API key and select a model. Get a key at https://openrouter.ai', 'suggester'); ?><br>
                                                <?php esc_html_e('Leave this field empty to disable this key.', 'suggester'); ?>
                                            </p>
                                        </td>
                                    </tr>

                                    <tr class="suggester-api-keys-section" style="<?php echo (isset($tool_settings['custom_api_keys']) && $tool_settings['custom_api_keys']) ? '' : 'display:none;'; ?>">
                                        <th scope="row">
                                            <label for="suggester-openrouter-key-2">
                                                <?php esc_html_e('OpenRouter API Key 2', 'suggester'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <div class="suggester-api-key-field">
                                                <input type="password" id="suggester-openrouter-key-2" 
                                                       name="openrouter_key_2" 
                                                       value="<?php echo esc_attr($tool_settings['openrouter_key_2'] ?? ''); ?>"
                                                       class="regular-text suggester-key-input">
                                                <button type="button" class="button suggester-toggle-visibility">
                                                    <span class="dashicons dashicons-visibility"></span>
                                                </button>
                                                
                                                <select name="openrouter_model_2" class="suggester-model-select">
                                                    <option value=""><?php esc_html_e('Select Model', 'suggester'); ?></option>
                                                    <optgroup label="<?php esc_html_e('OpenAI', 'suggester'); ?>">
                                                        <option value="openai/gpt-4o" <?php selected($tool_settings['openrouter_model_2'] ?? '', 'openai/gpt-4o'); ?>>GPT-4o</option>
                                                        <option value="openai/gpt-4o-mini" <?php selected($tool_settings['openrouter_model_2'] ?? '', 'openai/gpt-4o-mini'); ?>>GPT-4o Mini</option>
                                                        <option value="openai/gpt-4" <?php selected($tool_settings['openrouter_model_2'] ?? '', 'openai/gpt-4'); ?>>GPT-4</option>
                                                        <option value="openai/gpt-3.5-turbo" <?php selected($tool_settings['openrouter_model_2'] ?? '', 'openai/gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_html_e('Anthropic', 'suggester'); ?>">
                                                        <option value="anthropic/claude-opus-4" <?php selected($tool_settings['openrouter_model_2'] ?? '', 'anthropic/claude-opus-4'); ?>>Claude Opus 4</option>
                                                        <option value="anthropic/claude-sonnet-4" <?php selected($tool_settings['openrouter_model_2'] ?? '', 'anthropic/claude-sonnet-4'); ?>>Claude Sonnet 4</option>
                                                        <option value="anthropic/claude-3-opus" <?php selected($tool_settings['openrouter_model_2'] ?? '', 'anthropic/claude-3-opus'); ?>>Claude 3 Opus</option>
                                                        <option value="anthropic/claude-3-sonnet" <?php selected($tool_settings['openrouter_model_2'] ?? '', 'anthropic/claude-3-sonnet'); ?>>Claude 3 Sonnet</option>
                                                        <option value="anthropic/claude-3-haiku" <?php selected($tool_settings['openrouter_model_2'] ?? '', 'anthropic/claude-3-haiku'); ?>>Claude 3 Haiku</option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_html_e('Meta', 'suggester'); ?>">
                                                        <option value="meta-llama/llama-2-70b-chat" <?php selected($tool_settings['openrouter_model_2'] ?? '', 'meta-llama/llama-2-70b-chat'); ?>>Llama 2 70B</option>
                                                    </optgroup>
                                                </select>
                                            </div>
                                            <?php
                                            // Show status indicators and tracking for this key (only for existing tools)
                                            $tool_id = $this->current_tool_id;
                                            if ($tool_id > 0) {
                                                $last_key_index = get_transient('suggester_last_key_index_' . $tool_id);
                                                $key_error = get_transient('suggester_key_error_' . $tool_id . '_openrouter_2');
                                                
                                                if ($last_key_index == 2) {
                                                    echo '<span class="suggester-key-status suggester-key-last-used">' . esc_html__('Last used', 'suggester') . '</span>';
                                                }
                                                
                                                if ($key_error) {
                                                    echo '<span class="suggester-key-status suggester-key-error">' . esc_html__('Error: ', 'suggester') . esc_html(substr($key_error, 0, 50)) . (strlen($key_error) > 50 ? '...' : '') . '</span>';
                                                }
                                                
                                                // Get tracking statistics
                                                $suggester = Suggester::get_instance();
                                                $stats = $suggester->get_key_stats($tool_id, 'openrouter', 2);
                                                
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
                                                echo 'data-tool-id="' . esc_attr($tool_id) . '" ';
                                                echo 'data-api-type="openrouter" ';
                                                echo 'data-key-index="2" ';
                                                echo 'data-nonce="' . esc_attr(wp_create_nonce('suggester_reset_stats_' . $tool_id . '_openrouter_2')) . '" ';
                                                echo 'title="' . esc_attr__('Reset tracking statistics', 'suggester') . '">';
                                                echo '<span class="dashicons dashicons-update-alt"></span>';
                                                echo '</button>';
                                                echo '</div>';
                                            }
                                            }
                                            ?>
                                            <p class="description">
                                                <?php esc_html_e('Enter your second OpenRouter API key and select a model. Multiple keys enable intelligent load balancing.', 'suggester'); ?><br>
                                                <?php esc_html_e('Leave this field empty to disable this key.', 'suggester'); ?>
                                            </p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="suggester-prompt-template">
                                                <?php esc_html_e('Prompt Template', 'suggester'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <textarea id="suggester-prompt-template" 
                                                     name="prompt_template" 
                                                     rows="6" 
                                                     class="large-text"><?php echo esc_textarea($tool_settings['prompt_template'] ?? 'Generate {count} creative suggestions for {keyword} in {language}. Each suggestion should be on a separate line and be concise (maximum 10 words each).'); ?></textarea>
                                            <p class="description">
                                                <?php esc_html_e('Enter the prompt template for generating suggestions. Use the following variables:', 'suggester'); ?>
                                                <br>
                                                <code>{keyword}</code> - <?php esc_html_e('The keyword entered by the user', 'suggester'); ?>
                                                <br>
                                                <code>{count}</code> - <?php esc_html_e('Number of suggestions to generate (default: 3)', 'suggester'); ?>
                                                <br>
                                                <code>{language}</code> - <?php esc_html_e('Language for suggestions (default: English)', 'suggester'); ?>
                                            </p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="suggester-default-count">
                                                <?php esc_html_e('Default Suggestion Count', 'suggester'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="number" id="suggester-default-count" 
                                                   name="default_count" 
                                                   value="<?php echo esc_attr($tool_settings['default_count'] ?? '3'); ?>"
                                                   min="1" max="10" class="small-text">
                                            <p class="description">
                                                <?php esc_html_e('Default number of suggestions to generate (1-10)', 'suggester'); ?>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Templates Tab -->
                            <div id="templates-panel" class="suggester-tab-panel" 
                                 style="<?php echo esc_attr($current_tab === 'templates' ? 'display:block;' : 'display:none;'); ?>">
                                <h2><?php esc_html_e('Templates', 'suggester'); ?></h2>
                                
                                <div class="suggester-templates-grid">
                                    <?php
                                    // Include template functions
                                    require_once SUGGESTER_PLUGIN_DIR . 'assets/templates/index.php';
                                    
                                    // Get all available templates
                                    $templates = suggester_get_templates_info();
                                    
                                    // Get current template
                                    $current_template = isset($tool_settings['template']) ? $tool_settings['template'] : 'night-mode';
                                    
                                    // Display templates
                                    foreach ($templates as $template_id => $template) :
                                        $is_selected = ($template_id === $current_template);
                                        $colors = isset($template['colors']) ? $template['colors'] : array('#121212', '#1e1e1e', '#343434', '#bb86fc');
                                    ?>
                                    <div class="suggester-template-card <?php echo esc_attr($is_selected ? 'selected' : ''); ?>" data-template-id="<?php echo esc_attr($template_id); ?>">
                                        <div class="suggester-template-card-inner">
                                            <div class="suggester-template-preview">
                                                <div class="suggester-template-colors">
                                                    <?php foreach ($colors as $color) : ?>
                                                    <span class="suggester-template-color" style="background-color: <?php echo esc_attr($color); ?>"></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="suggester-template-info">
                                                <h3 class="suggester-template-name"><?php echo esc_html($template['name']); ?></h3>
                                                <p class="suggester-template-description"><?php echo esc_html($template['description']); ?></p>
                                            </div>
                                            <div class="suggester-template-select">
                                                <?php if ($is_selected) : ?>
                                                <span class="suggester-template-selected">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    <?php esc_html_e('Selected', 'suggester'); ?>
                                                </span>
                                                <?php else : ?>
                                                <button type="button" class="button suggester-select-template-btn">
                                                    <?php esc_html_e('Select', 'suggester'); ?>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <input type="hidden" name="template" id="suggester-selected-template" value="<?php echo esc_attr($current_template); ?>">
                            </div>
                            
                            <!-- Customization Tab -->
                            <div id="customization-panel" class="suggester-tab-panel" 
                                 style="<?php echo esc_attr($current_tab === 'customization' ? 'display:block;' : 'display:none;'); ?>">
                                <h2><?php esc_html_e('Customization', 'suggester'); ?></h2>
                                
                                <?php
                                // Get template config
                                $template_config = isset($tool_settings['template_config']) ? $tool_settings['template_config'] : array();
                                
                                // Default values
                                $show_favorites = isset($template_config['show_favorites']) ? (bool)$template_config['show_favorites'] : true;
                                $accent_color = isset($template_config['accent_color']) ? $template_config['accent_color'] : '#bb86fc';
                                $button_text_color = isset($template_config['button_text_color']) ? $template_config['button_text_color'] : '#ffffff';
                                ?>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="suggester-show-favorites">
                                                <?php esc_html_e('Favorites Menu', 'suggester'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <label class="suggester-switch">
                                                <input type="checkbox" id="suggester-show-favorites" 
                                                       name="template_config[show_favorites]" 
                                                       value="1" <?php checked($show_favorites, true); ?>>
                                                <span class="slider round"></span>
                                            </label>
                                            <p class="description">
                                                <?php esc_html_e('Show or hide the favorites section in the frontend.', 'suggester'); ?>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="suggester-accent-color">
                                                <?php esc_html_e('Accent Color', 'suggester'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" id="suggester-accent-color" 
                                                   name="template_config[accent_color]" 
                                                   value="<?php echo esc_attr($accent_color); ?>"
                                                   class="suggester-color-picker">
                                            <p class="description">
                                                <?php esc_html_e('Choose the accent color for buttons and highlights.', 'suggester'); ?>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="suggester-button-text-color">
                                                <?php esc_html_e('Button Text Color', 'suggester'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" id="suggester-button-text-color" 
                                                   name="template_config[button_text_color]" 
                                                   value="<?php echo esc_attr($button_text_color); ?>"
                                                   class="suggester-color-picker">
                                            <p class="description">
                                                <?php esc_html_e('Choose the text color for buttons.', 'suggester'); ?>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Custom API keys toggle
                const customApiKeysToggle = document.getElementById('suggester-custom-api-keys');
                const apiKeySections = document.querySelectorAll('.suggester-api-keys-section');
                const globalKeysNotice = document.querySelector('.suggester-global-keys-notice');
                
                if (customApiKeysToggle) {
                    customApiKeysToggle.addEventListener('change', function() {
                        apiKeySections.forEach(function(section) {
                            section.style.display = this.checked ? '' : 'none';
                        }.bind(this));
                        
                        // Toggle global keys notice
                        if (globalKeysNotice) {
                            globalKeysNotice.style.display = this.checked ? 'none' : '';
                        }
                    });
                }
                
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
                
                // Tab switching functionality
                const tabLinks = document.querySelectorAll('.suggester-nav-tab-wrapper .nav-tab');
                const tabPanels = document.querySelectorAll('.suggester-tab-panel');
                const currentTabInput = document.getElementById('suggester-current-tab');
                
                // Load the initial tab from URL or sessionStorage
                const urlParams = new URLSearchParams(window.location.search);
                const tabFromUrl = urlParams.get('tab');
                
                // Function to switch tabs
                function switchToTab(tabId, updateHistory = true) {
                    // Update active tab
                    tabLinks.forEach(function(link) {
                        if (link.getAttribute('data-tab') === tabId) {
                            link.classList.add('nav-tab-active');
                        } else {
                            link.classList.remove('nav-tab-active');
                        }
                    });
                    
                    // Show active panel
                    tabPanels.forEach(function(panel) {
                        panel.style.display = 'none';
                    });
                    const activePanel = document.getElementById(tabId + '-panel');
                    if (activePanel) {
                        activePanel.style.display = 'block';
                    }
                    
                    // Update hidden input for form submission
                    if (currentTabInput) {
                        currentTabInput.value = tabId;
                    }
                    
                    // Save to sessionStorage
                    sessionStorage.setItem('suggester_edit_reload_tab', tabId);
                    
                    // Update URL without page reload if requested
                    if (updateHistory && window.history && window.history.pushState) {
                        const url = new URL(window.location.href);
                        url.searchParams.set('tab', tabId);
                        window.history.pushState({tab: tabId}, '', url.toString());
                    }
                    
                    // Dispatch event for tab change
                    const event = new CustomEvent('suggesterTabChanged', {
                        detail: { tab: tabId }
                    });
                    document.dispatchEvent(event);
                }
                
                // Check if this is a first visit or page reload
                if (tabFromUrl) {
                    // URL parameter exists, use it and save it for future reloads
                    sessionStorage.setItem('suggester_edit_reload_tab', tabFromUrl);
                    switchToTab(tabFromUrl, false);
                } else {
                    // No tab in URL, check if we're reloading the page
                    const reloadTab = sessionStorage.getItem('suggester_edit_reload_tab');
                    const toolId = new URLSearchParams(window.location.search).get('tool_id');
                    const isPageReload = document.referrer === window.location.href || 
                                      (document.referrer.indexOf('page=suggester-tools') !== -1 && 
                                       document.referrer.indexOf('tool_id=' + toolId) !== -1);
                    
                    if (isPageReload && reloadTab && document.getElementById(reloadTab + '-panel')) {
                        // This is a page reload, use the saved tab
                        const newUrl = new URL(window.location.href);
                        newUrl.searchParams.set('tab', reloadTab);
                        
                        // Update URL without reloading
                        if (window.history && window.history.replaceState) {
                            window.history.replaceState({tab: reloadTab}, '', newUrl.toString());
                        }
                        
                        // Switch to the tab
                        switchToTab(reloadTab, false);
                    } else {
                        // This is a new navigation, use default 'basics' tab
                        sessionStorage.setItem('suggester_edit_reload_tab', 'basics');
                        switchToTab('basics', true);
                    }
                }
                
                // Add click event to tabs
                tabLinks.forEach(function(tabLink) {
                    tabLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        const tabId = this.getAttribute('data-tab');
                        switchToTab(tabId);
                    });
                });
                
                // Handle browser back/forward buttons
                window.addEventListener('popstate', function(e) {
                    if (e.state && e.state.tab) {
                        switchToTab(e.state.tab, false);
                    } else {
                        // Default to basics tab if state is empty
                        switchToTab('basics', false);
                    }
                });
                
                // Copy shortcode functionality
                const copyButton = document.querySelector('.suggester-copy-shortcode');
                if (copyButton) {
                    copyButton.addEventListener('click', function() {
                        const shortcode = this.getAttribute('data-shortcode');
                        navigator.clipboard.writeText(shortcode).then(function() {
                            // Change button text temporarily to indicate success
                            const originalHtml = copyButton.innerHTML;
                            copyButton.innerHTML = '<span class="dashicons dashicons-yes"></span>';
                            setTimeout(function() {
                                copyButton.innerHTML = originalHtml;
                            }, 1000);
                        }).catch(function() {
                            // Fallback for browsers that don't support clipboard API
                            const textarea = document.createElement('textarea');
                            textarea.value = shortcode;
                            textarea.style.position = 'fixed';  // Prevent scrolling to bottom
                            document.body.appendChild(textarea);
                            textarea.focus();
                            textarea.select();
                            
                            try {
                                document.execCommand('copy');
                                copyButton.innerHTML = '<span class="dashicons dashicons-yes"></span>';
                                setTimeout(function() {
                                    copyButton.innerHTML = originalHtml;
                                }, 1000);
                            } catch (err) {
                                console.error('Failed to copy shortcode', err);
                            }
                            
                            document.body.removeChild(textarea);
                        });
                    });
                }
                
                // Template selection functionality
                const templateCards = document.querySelectorAll('.suggester-template-card');
                const selectedTemplateInput = document.getElementById('suggester-selected-template');
                
                templateCards.forEach(function(card) {
                    const selectBtn = card.querySelector('.suggester-select-template-btn');
                    
                    if (selectBtn) {
                        selectBtn.addEventListener('click', function() {
                            const templateId = card.getAttribute('data-template-id');
                            
                            // Update hidden input
                            if (selectedTemplateInput) {
                                selectedTemplateInput.value = templateId;
                            }
                            
                            // Update accent color based on selected template
                            let accentColor = '';
                            if (templateId === 'light') {
                                accentColor = '#1e88e5'; // Light template default accent color
                            } else if (templateId === 'night-mode') {
                                accentColor = '#bb86fc'; // Night Mode template default accent color
                            }
                            
                            if (accentColor) {
                                const accentColorInput = document.getElementById('suggester-accent-color');
                                if (accentColorInput) {
                                    // Set the input value
                                    accentColorInput.value = accentColor;
                                    
                                    // Update WordPress color picker if it exists
                                    if (window.wp && wp.colorPicker && jQuery) {
                                        jQuery(accentColorInput).wpColorPicker('color', accentColor);
                                    }
                                }
                            }
                            
                            // Update UI
                            templateCards.forEach(function(c) {
                                c.classList.remove('selected');
                                
                                const btn = c.querySelector('.suggester-select-template-btn');
                                const selectedSpan = c.querySelector('.suggester-template-selected');
                                
                                // Remove selected indicator if exists
                                if (selectedSpan) {
                                    selectedSpan.remove();
                                }
                                
                                // Create select button if needed
                                if (!btn) {
                                    const newBtn = document.createElement('button');
                                    newBtn.type = 'button';
                                    newBtn.className = 'button suggester-select-template-btn';
                                    newBtn.textContent = 'Select';
                                    
                                    const selectDiv = c.querySelector('.suggester-template-select');
                                    if (selectDiv) {
                                        selectDiv.innerHTML = '';
                                        selectDiv.appendChild(newBtn);
                                        
                                        // Add click event to new button
                                        newBtn.addEventListener('click', function() {
                                            const id = c.getAttribute('data-template-id');
                                            if (selectedTemplateInput) {
                                                selectedTemplateInput.value = id;
                                            }
                                            updateTemplateSelection(id);
                                        });
                                    }
                                }
                            });
                            
                            // Mark current as selected
                            card.classList.add('selected');
                            
                            // Replace button with selected indicator
                            const selectDiv = card.querySelector('.suggester-template-select');
                            if (selectDiv) {
                                selectDiv.innerHTML = `
                                    <span class="suggester-template-selected">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Selected
                                    </span>
                                `;
                            }
                        });
                    }
                });
                
                function updateTemplateSelection(templateId) {
                    templateCards.forEach(function(card) {
                        const cardTemplateId = card.getAttribute('data-template-id');
                        const isSelected = cardTemplateId === templateId;
                        
                        if (isSelected) {
                            card.classList.add('selected');
                            const selectDiv = card.querySelector('.suggester-template-select');
                            if (selectDiv) {
                                selectDiv.innerHTML = `
                                    <span class="suggester-template-selected">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Selected
                                    </span>
                                `;
                            }
                            
                            // Update accent color based on selected template
                            let accentColor = '';
                            if (templateId === 'light') {
                                accentColor = '#1e88e5'; // Light template default accent color
                            } else if (templateId === 'night-mode') {
                                accentColor = '#bb86fc'; // Night Mode template default accent color
                            }
                            
                            if (accentColor) {
                                const accentColorInput = document.getElementById('suggester-accent-color');
                                if (accentColorInput) {
                                    // Set the input value
                                    accentColorInput.value = accentColor;
                                    
                                    // Update WordPress color picker if it exists
                                    if (window.wp && wp.colorPicker && jQuery) {
                                        jQuery(accentColorInput).wpColorPicker('color', accentColor);
                                    }
                                }
                            }
                        } else {
                            card.classList.remove('selected');
                            const selectDiv = card.querySelector('.suggester-template-select');
                            if (selectDiv) {
                                selectDiv.innerHTML = `
                                    <button type="button" class="button suggester-select-template-btn">
                                        Select
                                    </button>
                                `;
                                
                                // Add click event to new button
                                const newBtn = selectDiv.querySelector('.suggester-select-template-btn');
                                if (newBtn) {
                                    newBtn.addEventListener('click', function() {
                                        if (selectedTemplateInput) {
                                            selectedTemplateInput.value = cardTemplateId;
                                        }
                                        updateTemplateSelection(cardTemplateId);
                                    });
                                }
                            }
                        }
                    });
                }
                
                // Initialize color pickers if available
                if (window.wp && wp.colorPicker) {
                    const colorInputs = document.querySelectorAll('.suggester-color-picker');
                    colorInputs.forEach(function(input) {
                        jQuery(input).wpColorPicker();
                    });
                } else {
                    // Fallback if WordPress color picker is not available
                    const colorInputs = document.querySelectorAll('.suggester-color-picker');
                    colorInputs.forEach(function(input) {
                        input.type = 'color';
                    });
                }
                
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
            .suggester-tool-edit-container {
                margin-top: 20px;
            }
            
            .suggester-tool-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            
            .suggester-tool-shortcode {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .suggester-tool-shortcode code {
                padding: 5px;
                background: #f6f7f7;
                border-radius: 3px;
            }
            
            .suggester-label {
                font-weight: 600;
            }
            
            .suggester-action-buttons {
                display: flex;
                gap: 10px;
            }
            
            .suggester-tabs-wrapper {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
            }
            
            .suggester-tab-content {
                padding: 20px;
                min-height: 300px;
            }
            
            .suggester-tab-panel h2 {
                margin-top: 0;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            
            /* Templates Tab */
            .suggester-templates-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                margin-top: 20px;
            }
            
            @media (max-width: 1200px) {
                .suggester-templates-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            
            @media (max-width: 782px) {
                .suggester-templates-grid {
                    grid-template-columns: 1fr;
                }
            }
            
            .suggester-template-card {
                border: 2px solid #e5e5e5;
                border-radius: 5px;
                overflow: hidden;
                transition: all 0.2s ease;
            }
            
            .suggester-template-card.selected {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
            }
            
            .suggester-template-card-inner {
                padding: 15px;
            }
            
            .suggester-template-preview {
                margin-bottom: 15px;
            }
            
            .suggester-template-colors {
                display: flex;
                gap: 10px;
            }
            
            .suggester-template-color {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                border: 1px solid rgba(0, 0, 0, 0.1);
            }
            
            .suggester-template-info {
                margin-bottom: 15px;
            }
            
            .suggester-template-name {
                margin: 0 0 5px 0;
                font-size: 16px;
            }
            
            .suggester-template-description {
                margin: 0;
                color: #666;
                font-size: 13px;
            }
            
            .suggester-template-select {
                display: flex;
                justify-content: flex-end;
            }
            
            .suggester-template-selected {
                display: flex;
                align-items: center;
                gap: 5px;
                color: #2271b1;
                font-weight: 500;
            }
            
            /* Customization Tab */
            .suggester-switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
                margin-right: 10px;
                vertical-align: middle;
            }
            
            .suggester-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .suggester-switch .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
            }
            
            .suggester-switch .slider:before {
                position: absolute;
                content: "";
                height: 16px;
                width: 16px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
            }
            
            .suggester-switch input:checked + .slider {
                background-color: #2196F3;
            }
            
            .suggester-switch input:focus + .slider {
                box-shadow: 0 0 1px #2196F3;
            }
            
            .suggester-switch input:checked + .slider:before {
                transform: translateX(26px);
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
            
            .suggester-switch-label {
                margin-left: 10px;
                vertical-align: middle;
            }
            
            #suggester-prompt-template {
                font-family: monospace;
                line-height: 1.5;
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
            
            /* Global Keys Notice */
            .suggester-notice {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 12px 16px;
                border-radius: 4px;
                font-size: 14px;
                line-height: 1.4;
            }
            
            .suggester-notice-info {
                background-color: #e7f3ff;
                border: 1px solid #b3d9ff;
                color: #0073aa;
            }
            
            .suggester-notice .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                flex-shrink: 0;
            }
            
            .suggester-notice-text {
                flex: 1;
            }
            
            .suggester-notice-text a {
                color: #0073aa;
                text-decoration: none;
                font-weight: 500;
                margin-left: 8px;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            
            .suggester-notice-text a:hover {
                text-decoration: underline;
            }
            
            .suggester-notice-text a .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
        </style>
        <?php
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
