<?php
/**
 * Suggester Statistics Class
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Statistics Class
 */
class Suggester_Statistics {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_suggester_track_action', array($this, 'ajax_track_action'));
        add_action('wp_ajax_nopriv_suggester_track_action', array($this, 'ajax_track_action'));
        add_action('wp_ajax_suggester_export_errors', array($this, 'ajax_export_errors'));
        add_action('wp_ajax_suggester_clear_errors', array($this, 'ajax_clear_errors'));
        add_action('wp_ajax_suggester_export_api_errors', array($this, 'ajax_export_api_errors'));
        add_action('wp_ajax_suggester_clear_api_errors', array($this, 'ajax_clear_api_errors'));
        add_action('wp_ajax_suggester_load_api_errors_page', array($this, 'ajax_load_api_errors_page'));
        
        // Schedule error cleanup at the beginning of each month
        if (!wp_next_scheduled('suggester_cleanup_errors')) {
            wp_schedule_event(strtotime('first day of next month'), 'monthly', 'suggester_cleanup_errors');
        }
        add_action('suggester_cleanup_errors', array($this, 'cleanup_monthly_errors'));
        
        // Schedule usage data cleanup (keep data for 1 year)
        if (!wp_next_scheduled('suggester_cleanup_usage')) {
            wp_schedule_event(time() + WEEK_IN_SECONDS, 'weekly', 'suggester_cleanup_usage');
        }
        add_action('suggester_cleanup_usage', array($this, 'cleanup_old_usage_data'));
        
        // Create error table on activation
        $this->maybe_create_error_table();
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
        
        // Also create usage tracking table
        $this->maybe_create_usage_table();
    }
    
    /**
     * Create usage tracking table if it doesn't exist
     */
    private function maybe_create_usage_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_usage';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
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
            dbDelta($sql);
        } else {
            // Check if action_type column exists, if not add it
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'action_type'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN action_type varchar(20) DEFAULT 'usage' AFTER tool_name");
                $wpdb->query("ALTER TABLE $table_name ADD INDEX action_type (action_type)");
            }
        }
    }
    
    /**
     * Standardized error logging function for the Suggester plugin
     *
     * @param string $message Error message
     * @param string $type Error type (api_key_failure, user_input, permission, etc.)
     * @param string $severity Severity level (error, warning, notice)
     * @param array $context Additional context
     */
    public function log_suggester_error($message, $type = 'general', $severity = 'error', $context = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_errors';
        
        // Ensure the error table exists
        $this->maybe_create_error_table();
        
        // Generate key field name for API errors
        $key_field_name = null;
        if ($type === 'api_key_failure' && isset($context['api_type']) && isset($context['key_index'])) {
            $api_type = $context['api_type'];
            $key_index = $context['key_index'];
            
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
        }
        
        // Prepare error data
        $error_data = array(
            'error_message' => sanitize_text_field($message),
            'error_type' => sanitize_key($type),
            'severity' => sanitize_key($severity),
            'user_id' => get_current_user_id(),
            'user_input' => isset($context['user_input']) ? sanitize_textarea_field($context['user_input']) : null,
            'tool_name' => isset($context['tool_name']) ? sanitize_text_field($context['tool_name']) : null,
            'tool_id' => isset($context['tool_id']) ? absint($context['tool_id']) : null,
            'key_field_name' => $key_field_name,
            'api_type' => isset($context['api_type']) ? sanitize_key($context['api_type']) : null,
            'key_index' => isset($context['key_index']) ? absint($context['key_index']) : null,
            'ip_address' => $this->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 500) : null,
            'created_at' => current_time('mysql')
        );
        
        // Insert error
        $result = $wpdb->insert($table_name, $error_data);
        
        // Debug logging if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Suggester Error Logged: " . json_encode(array(
                'result' => $result,
                'type' => $type,
                'message' => $message,
                'context' => $context
            )));
        }
        
        return $result !== false;
    }
    
    /**
     * Log an error to the database (legacy method - now uses log_suggester_error)
     *
     * @param string $message Error message
     * @param string $type Error type (api, user_input, permission, etc.)
     * @param string $severity Severity level (error, warning, notice)
     * @param array $context Additional context
     */
    public function log_error($message, $type = 'general', $severity = 'error', $context = array()) {
        return $this->log_suggester_error($message, $type, $severity, $context);
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
     * Track user actions (favorites and copies)
     */
    public function ajax_track_action() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'suggester_frontend_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $action_type = sanitize_key($_POST['action_type'] ?? '');
        $tool_id = absint($_POST['tool_id'] ?? 0);
        
        if (!in_array($action_type, array('favorite', 'copy'))) {
            wp_send_json_error('Invalid action type');
        }
        
        // Get current statistics
        $current_stats = get_option('suggester_monthly_actions', array(
            'favorites' => 0,
            'copies' => 0
        ));
        
        // Increment the appropriate counter
        if ($action_type === 'copy') {
            $current_stats['copies']++;
        } else {
            $current_stats[$action_type . 's']++;
        }
        
        // Update statistics
        update_option('suggester_monthly_actions', $current_stats);
        
        // Track tool actions (favorites and copies only)
        if ($tool_id > 0) {
            $tool_stats = get_option('suggester_tool_stats_' . $tool_id, array(
                'uses' => 0,
                'favorites' => 0,
                'copies' => 0,
                'created_date' => current_time('mysql')
            ));
            
            // Only increment the specific action type (favorite or copy)
            if ($action_type === 'copy') {
                $tool_stats['copies']++;
            } else {
                $tool_stats[$action_type . 's']++;
            }
            
            update_option('suggester_tool_stats_' . $tool_id, $tool_stats);
        }
        
        // NEW: Also log to usage table for daily tracking
        $this->log_action_event($tool_id, $action_type);
        
        wp_send_json_success('Action tracked');
    }
    
    /**
     * Log action event (favorite/copy) to the usage tracking table
     *
     * @param int $tool_id Tool ID
     * @param string $action_type Action type (favorite, copy)
     */
    private function log_action_event($tool_id, $action_type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_usage';
        
        // Ensure the usage table exists
        $this->maybe_create_usage_table();
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return false;
        }
        
        // Get tool name
        $tool_name = 'Unknown Tool';
        if ($tool_id > 0) {
            $tool_settings = get_option('suggester_tool_settings_' . $tool_id);
            if ($tool_settings && isset($tool_settings['name'])) {
                $tool_name = $tool_settings['name'];
            }
        }
        
        // Get user IP
        $ip_address = $this->get_user_ip();
        
        // Insert action event with action_type information
        $result = $wpdb->insert(
            $table_name,
            array(
                'tool_id' => $tool_id,
                'tool_name' => $tool_name,
                'action_type' => $action_type, // Add action_type to track favorites/copies
                'user_id' => get_current_user_id(),
                'ip_address' => $ip_address,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 500) : null,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get general statistics summary
     */
    public function get_general_statistics() {
        $cache_key = 'suggester_general_stats_' . current_time('Y-m-d-H');
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $stats = array();
        $now = current_time('timestamp');
        
        // Today's statistics
        $stats['today'] = array(
            'current' => $this->get_usage_count('today'),
            'previous' => $this->get_usage_count('yesterday'),
            'label' => __('Today', 'suggester')
        );
        
        // This week's statistics
        $stats['week'] = array(
            'current' => $this->get_usage_count('this_week'),
            'previous' => $this->get_usage_count('last_week'),
            'label' => __('This Week', 'suggester')
        );
        
        // This month's statistics
        $stats['month'] = array(
            'current' => $this->get_usage_count('this_month'),
            'previous' => $this->get_usage_count('last_month'),
            'label' => __('This Month', 'suggester')
        );
        
        // This year's statistics (no comparison)
        $stats['year'] = array(
            'current' => $this->get_usage_count('this_year'),
            'previous' => 0,
            'label' => __('This Year', 'suggester')
        );
        
        // Calculate percentages
        foreach ($stats as $key => &$stat) {
            if ($key === 'year') {
                $stat['percentage'] = null;
                $stat['trend'] = 'neutral';
            } else {
                if ($stat['previous'] > 0) {
                    $change = (($stat['current'] - $stat['previous']) / $stat['previous']) * 100;
                    $stat['percentage'] = round($change, 1);
                    $stat['trend'] = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral');
                } else {
                    $stat['percentage'] = $stat['current'] > 0 ? 100 : 0;
                    $stat['trend'] = $stat['current'] > 0 ? 'up' : 'neutral';
                }
            }
        }
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $stats, '', 3600);
        
        return $stats;
    }
    
    /**
     * Get usage count for a specific period
     */
    private function get_usage_count($period) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'suggester_usage';
        
        switch ($period) {
            case 'today':
                $start = current_time('Y-m-d') . ' 00:00:00';
                $end = current_time('Y-m-d') . ' 23:59:59';
                break;
                
            case 'yesterday':
                $start = gmdate('Y-m-d', strtotime('-1 day', current_time('timestamp'))) . ' 00:00:00';
                $end = gmdate('Y-m-d', strtotime('-1 day', current_time('timestamp'))) . ' 23:59:59';
                break;
                
            case 'this_week':
                $start = gmdate('Y-m-d', strtotime('monday this week', current_time('timestamp'))) . ' 00:00:00';
                $end = current_time('Y-m-d') . ' 23:59:59';
                break;
                
            case 'last_week':
                $start = gmdate('Y-m-d', strtotime('monday last week', current_time('timestamp'))) . ' 00:00:00';
                $end = gmdate('Y-m-d', strtotime('sunday last week', current_time('timestamp'))) . ' 23:59:59';
                break;
                
            case 'this_month':
                $start = current_time('Y-m') . '-01 00:00:00';
                $end = current_time('Y-m-d') . ' 23:59:59';
                break;
                
            case 'last_month':
                $start = gmdate('Y-m-01', strtotime('first day of last month', current_time('timestamp'))) . ' 00:00:00';
                $end = gmdate('Y-m-t', strtotime('last day of last month', current_time('timestamp'))) . ' 23:59:59';
                break;
                
            case 'this_year':
                $start = current_time('Y') . '-01-01 00:00:00';
                $end = current_time('Y-m-d') . ' 23:59:59';
                break;
                
            default:
                return 0;
        }
        
        // Check if usage table exists, if not return 0
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0;
        }
        
        // Check if action_type column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'action_type'");
        
        if (!empty($column_exists)) {
            // Count only usage events (excluding favorites and copies) for General Statistics Summary
            return intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE action_type = 'usage' AND created_at BETWEEN %s AND %s",
                $start,
                $end
            )));
        } else {
            // Fallback for legacy data (before action_type column existed)
            return intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE created_at BETWEEN %s AND %s",
                $start,
                $end
            )));
        }
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
     * Get tools statistics for table
     */
    public function get_tools_statistics() {
        $tools = $this->get_all_tools();
        $tools_stats = array();
        
        foreach ($tools as $tool_id => $tool) {
            $tool_stats = get_option('suggester_tool_stats_' . $tool_id, array(
                'uses' => 0,
                'favorites' => 0,
                'copies' => 0,
                'created_date' => current_time('mysql')
            ));
            
            $tools_stats[] = array(
                'id' => $tool_id,
                'name' => $tool['name'],
                'shortcode' => '[suggester id=\'' . $tool_id . '\']',
                'uses' => $tool_stats['uses'],
                'favorites' => $tool_stats['favorites'],
                'copies' => $tool_stats['copies'],
                'created_date' => $tool_stats['created_date']
            );
        }
        
        return $tools_stats;
    }
    

    
    /**
     * Get favorites and copies statistics
     */
    public function get_monthly_actions() {
        // Check if we need to reset monthly stats
        $this->maybe_reset_monthly_stats();
        
        return get_option('suggester_monthly_actions', array(
            'favorites' => 0,
            'copies' => 0
        ));
    }
    
    /**
     * Reset monthly statistics if it's a new month
     */
    private function maybe_reset_monthly_stats() {
        $last_reset = get_option('suggester_monthly_reset_date');
        $current_month = current_time('Y-m');
        
        // Fix existing data migration (one-time fix)
        $this->fix_copy_data_migration();
        
        // If no reset date recorded or it's a new month
        if (!$last_reset || $last_reset !== $current_month) {
            // Reset monthly stats
            update_option('suggester_monthly_actions', array(
                'favorites' => 0,
                'copies' => 0
            ));
            
            // Update reset date
            update_option('suggester_monthly_reset_date', $current_month);
        }
    }
    
    /**
     * Fix the typo in stored copy statistics (one-time migration)
     */
    private function fix_copy_data_migration() {
        $migration_done = get_option('suggester_copy_migration_done', false);
        
        if (!$migration_done) {
            // Fix monthly actions
            $monthly_actions = get_option('suggester_monthly_actions', array());
            if (isset($monthly_actions['copys'])) {
                $monthly_actions['copies'] = ($monthly_actions['copies'] ?? 0) + $monthly_actions['copys'];
                unset($monthly_actions['copys']);
                update_option('suggester_monthly_actions', $monthly_actions);
            }
            
            // Fix tool statistics
            $tool_counter = get_option('suggester_tool_counter', 0);
            for ($i = 1; $i <= $tool_counter; $i++) {
                $tool_stats = get_option('suggester_tool_stats_' . $i, array());
                if (isset($tool_stats['copys'])) {
                    $tool_stats['copies'] = ($tool_stats['copies'] ?? 0) + $tool_stats['copys'];
                    unset($tool_stats['copys']);
                    update_option('suggester_tool_stats_' . $i, $tool_stats);
                }
            }
            
            // Mark migration as done
            update_option('suggester_copy_migration_done', true);
        }
    }
    
    /**
     * Get recent errors for display
     */
    public function get_recent_errors($limit = 5, $page = 1) {
        global $wpdb;
        
        if (!current_user_can('manage_options')) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'suggester_errors';
        $offset = ($page - 1) * $limit;
        
        $errors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A);
        
        // Get total count for pagination
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        return array(
            'errors' => $errors,
            'total' => $total_count,
            'pages' => ceil($total_count / $limit)
        );
    }
    
    /**
     * Export errors as CSV
     */
    public function ajax_export_errors() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'suggester_errors';
        
        // Get all errors for current month
        $start_of_month = current_time('Y-m') . '-01 00:00:00';
        $end_of_month = current_time('Y-m-t') . ' 23:59:59';
        
        $errors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE created_at BETWEEN %s AND %s ORDER BY created_at DESC",
            $start_of_month,
            $end_of_month
        ), ARRAY_A);
        
        if (empty($errors)) {
            wp_send_json_error('No errors found for this month');
        }
        
        // Generate CSV content
        $csv_content = "ID,Error Message,Error Type,Severity,User ID,User Input,Tool Name,Tool ID,IP Address,Created At\n";
        
        foreach ($errors as $error) {
            $csv_content .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",%s,\"%s\",\"%s\",%s,\"%s\",\"%s\"\n",
                $error['id'],
                str_replace('"', '""', $error['error_message']),
                $error['error_type'],
                $error['severity'],
                $error['user_id'] ?: '',
                str_replace('"', '""', $error['user_input'] ?: ''),
                str_replace('"', '""', $error['tool_name'] ?: ''),
                $error['tool_id'] ?: '',
                $error['ip_address'],
                $error['created_at']
            );
        }
        
        $filename = 'suggester-errors-' . current_time('Y-m') . '.csv';
        
        wp_send_json_success(array(
            'filename' => $filename,
            'content' => $csv_content
        ));
    }
    
    /**
     * Clear all error logs
     */
    public function ajax_clear_errors() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'suggester_clear_errors')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'suggester_errors';
        
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        if ($result !== false) {
            wp_send_json_success('All error logs cleared successfully');
        } else {
            wp_send_json_error('Failed to clear error logs');
        }
    }
    
    /**
     * Monthly cleanup of error logs
     */
    public function cleanup_monthly_errors() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'suggester_errors';
        
        // Delete errors older than 1 month
        $one_month_ago = gmdate('Y-m-d H:i:s', strtotime('-1 month'));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $one_month_ago
        ));
    }
    
    /**
     * Weekly cleanup of old usage data (keep 1 year)
     */
    public function cleanup_old_usage_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'suggester_usage';
        
        // Delete usage data older than 1 year
        $one_year_ago = gmdate('Y-m-d H:i:s', strtotime('-1 year'));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $one_year_ago
        ));
    }
    
    /**
     * Render statistics dashboard
     */
    public function render_statistics() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'suggester'));
        }
        
        $general_stats = $this->get_general_statistics();
        $tools_stats = $this->get_tools_statistics();
        $monthly_actions = $this->get_monthly_actions();
        $api_errors = $this->get_api_errors(5, 1); // Always start with page 1, AJAX will handle pagination
        
        ?>
        <div class="suggester-statistics-dashboard">
            <!-- Section One: General Statistics Summary -->
            <div class="suggester-stats-section">
                <h3><?php esc_html_e('General Statistics Summary', 'suggester'); ?></h3>
                <div class="suggester-stats-cards">
                    <?php foreach ($general_stats as $key => $stat) : ?>
                        <div class="suggester-stat-card">
                            <div class="stat-label"><?php echo esc_html($stat['label']); ?></div>
                            <div class="stat-value"><?php echo esc_html($stat['current']); ?></div>
                            <?php if ($stat['percentage'] !== null) : ?>
                                <div class="stat-change stat-<?php echo esc_attr($stat['trend']); ?>">
                                    <span class="stat-arrow">
                                        <?php if ($stat['trend'] === 'up') : ?>
                                            ↑
                                        <?php elseif ($stat['trend'] === 'down') : ?>
                                            ↓
                                        <?php else : ?>
                                            →
                                        <?php endif; ?>
                                    </span>
                                    <?php 
                                    if ($stat['trend'] !== 'neutral') {
                                        echo esc_html(abs($stat['percentage']) . '%');
                                    } else {
                                        echo esc_html('0%');
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Section Two: Tools Statistics Table -->
            <div class="suggester-stats-section">
                <h3><?php esc_html_e('Tools Statistics', 'suggester'); ?></h3>
                <p class="description">
                    <?php esc_html_e('The following statistics show the number of uses, copies, and favorites for each tool since its creation date.', 'suggester'); ?>
                </p>
                
                <div class="suggester-tools-stats-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Tool Name', 'suggester'); ?></th>
                                <th><?php esc_html_e('Shortcode', 'suggester'); ?></th>
                                <th><?php esc_html_e('Uses', 'suggester'); ?></th>
                                <th><?php esc_html_e('Copies', 'suggester'); ?></th>
                                <th><?php esc_html_e('Favorites', 'suggester'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="tools-stats-tbody">
                            <?php if (!empty($tools_stats)) : ?>
                                <?php foreach ($tools_stats as $tool) : ?>
                                    <tr>
                                        <td><?php echo esc_html($tool['name']); ?></td>
                                        <td><code><?php echo esc_html($tool['shortcode']); ?></code></td>
                                        <td><?php echo esc_html($tool['uses']); ?></td>
                                        <td><?php echo esc_html($tool['copies']); ?></td>
                                        <td><?php echo esc_html($tool['favorites']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="no-items">
                                        <?php esc_html_e('No tools found.', 'suggester'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Section Three: Favorites and Copies -->
            <div class="suggester-stats-section">
                <h3><?php esc_html_e('Favorites and Copies', 'suggester'); ?></h3>
                <div class="suggester-action-stats">
                    <div class="action-stats-cards">
                        <div class="action-stat-card">
                            <div class="stat-label"><?php esc_html_e('Favorites This Month', 'suggester'); ?></div>
                            <div class="stat-value"><?php echo esc_html($monthly_actions['favorites']); ?></div>
                        </div>
                        <div class="action-stat-card">
                            <div class="stat-label"><?php esc_html_e('Copies This Month', 'suggester'); ?></div>
                            <div class="stat-value"><?php echo esc_html($monthly_actions['copies']); ?></div>
                        </div>
                    </div>
                    
                    <div class="analytics-recommendation">
                        <div class="recommendation-icon">💡</div>
                        <div class="recommendation-text">
                            <strong><?php esc_html_e('Recommendation:', 'suggester'); ?></strong>
                            <?php esc_html_e('For more accurate statistics and tracking of important events, we recommend connecting the plugin with Google Analytics.', 'suggester'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Four: API Errors -->
            <div class="suggester-stats-section">
                <h3><?php esc_html_e('API Errors', 'suggester'); ?></h3>
                <p class="description">
                    <?php esc_html_e('This section displays API key failures logged by the plugin. Errors are automatically deleted at the beginning of each month.', 'suggester'); ?>
                </p>
                
                <!-- API Errors Container (will be loaded via AJAX) -->
                <div id="api-errors-container">
                    <?php $this->render_api_errors_table($api_errors, 1); ?>
                </div>
                
                <div class="api-errors-actions">
                    <button type="button" class="button" id="export-api-errors">
                        <?php esc_html_e('Export This Month\'s Errors (CSV)', 'suggester'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="clear-api-errors">
                        <?php esc_html_e('Clear All API Errors', 'suggester'); ?>
                    </button>
                </div>
                
                <div class="api-errors-notice">
                    <p class="description">
                        <strong><?php esc_html_e('Note:', 'suggester'); ?></strong>
                        <?php esc_html_e('API errors are automatically deleted at the beginning of each month to prevent database bloat. Export errors before the month ends if you need to keep them.', 'suggester'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- CSS Styles -->
        <style>
            .suggester-statistics-dashboard {
                max-width: 1200px;
            }
            
            .suggester-stats-section {
                margin-bottom: 30px;
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
            }
            
            .suggester-stats-section h3 {
                margin-top: 0;
                border-bottom: 1px solid #e1e1e1;
                padding-bottom: 10px;
            }
            
            .suggester-stats-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            
            .suggester-stat-card {
                background: #f8f9fa;
                border: 1px solid #e1e1e1;
                border-radius: 6px;
                padding: 20px;
                text-align: center;
                position: relative;
            }
            
            .stat-label {
                font-weight: 600;
                color: #646970;
                margin-bottom: 10px;
                font-size: 14px;
            }
            
            .stat-value {
                font-size: 28px;
                font-weight: bold;
                color: #1d2327;
                margin-bottom: 10px;
            }
            
            .stat-change {
                font-size: 14px;
                font-weight: 600;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }
            
            .stat-up {
                color: #00a32a;
            }
            
            .stat-down {
                color: #d63638;
            }
            
            .stat-neutral {
                color: #646970;
            }
            
            .stat-arrow {
                font-size: 16px;
            }
            

            
            .action-stats-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 20px;
            }
            
            .action-stat-card {
                background: #f8f9fa;
                border: 1px solid #e1e1e1;
                border-radius: 6px;
                padding: 20px;
                text-align: center;
            }
            
            .analytics-recommendation {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 6px;
                padding: 15px;
                display: flex;
                align-items: flex-start;
                gap: 10px;
            }
            
            .recommendation-icon {
                font-size: 20px;
                margin-top: 2px;
            }
            
            .api-errors-content {
                padding: 20px 0;
            }
            
            .api-errors-content .description {
                margin: 0;
                font-style: italic;
                color: #646970;
            }
            
            .na {
                color: #646970;
                font-style: italic;
            }
            
            @media (max-width: 782px) {
                .suggester-stats-cards,
                .action-stats-cards {
                    grid-template-columns: 1fr;
                }
                
                .analytics-recommendation {
                    flex-direction: column;
                    text-align: center;
                }
            }
            
            .api-errors-table {
                margin: 20px 0;
            }
            
            .api-errors-table table {
                margin-bottom: 15px;
            }
            
            .error-type-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
            }
            
            .error-type-api_key_failure {
                background: #fff3e0;
                color: #e65100;
                border: 1px solid #ffcc80;
            }
            
            .error-message-cell {
                max-width: 300px;
            }
            
            .error-message {
                display: block;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                cursor: help;
            }
            
            .api-errors-pagination {
                margin: 15px 0;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 4px;
            }
            
            .pagination-info {
                text-align: center;
                margin-bottom: 15px;
            }
            
            .pagination-info .description {
                margin: 0;
                font-style: italic;
                color: #646970;
            }
            
            .pagination-controls {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                flex-wrap: wrap;
            }
            
            .pagination-links {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .page-numbers {
                display: inline-block;
                padding: 8px 12px;
                text-decoration: none;
                border: 1px solid #c3c4c7;
                background: #fff;
                color: #50575e;
                border-radius: 3px;
                font-size: 14px;
                line-height: 1;
                min-width: 20px;
                text-align: center;
                transition: all 0.2s ease;
                cursor: pointer;
            }
            
            .page-numbers:hover {
                background: #f6f7f7;
                border-color: #8c8f94;
                color: #1d2327;
                text-decoration: none;
            }
            
            .page-numbers.current {
                background: #2271b1;
                border-color: #2271b1;
                color: #fff;
                font-weight: 600;
                cursor: default;
            }
            
            .page-numbers.dots {
                border: none;
                background: transparent;
                color: #646970;
                padding: 8px 4px;
                pointer-events: none;
                cursor: default;
            }
            
            .pagination-controls .button {
                margin: 0;
            }
            
            .pagination-controls button[data-page] {
                cursor: pointer;
            }
            
            .pagination-controls button[data-page]:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            #api-errors-container.loading {
                opacity: 0.6;
                pointer-events: none;
            }
            
            .api-errors-loading {
                text-align: center;
                padding: 20px;
                color: #646970;
                font-style: italic;
            }
            
            @media (max-width: 600px) {
                .pagination-controls {
                    flex-direction: column;
                    gap: 15px;
                }
                
                .pagination-links {
                    flex-wrap: wrap;
                    justify-content: center;
                }
                
                .page-numbers {
                    padding: 6px 10px;
                    font-size: 13px;
                }
            }
            
            .api-errors-actions {
                margin: 20px 0;
                padding: 15px 0;
                border-top: 1px solid #e1e1e1;
            }
            
            .api-errors-actions .button {
                margin-right: 10px;
            }
            
            .api-errors-notice {
                margin-top: 15px;
                padding: 10px;
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 4px;
            }
            
            .no-api-errors {
                padding: 20px;
                text-align: center;
                color: #646970;
                font-style: italic;
            }
        </style>
        
        <!-- JavaScript -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // API Errors Pagination functionality
                function setupApiErrorsPagination() {
                    const container = document.getElementById('api-errors-container');
                    if (!container) return;
                    
                    // Add event delegation for pagination buttons
                    container.addEventListener('click', function(e) {
                        if (e.target.hasAttribute('data-page')) {
                            e.preventDefault();
                            const page = parseInt(e.target.getAttribute('data-page'));
                            loadApiErrorsPage(page);
                        }
                    });
                }
                
                function loadApiErrorsPage(page) {
                    const container = document.getElementById('api-errors-container');
                    if (!container) return;
                    
                    // Add loading state
                    container.classList.add('loading');
                    container.innerHTML = '<div class="api-errors-loading"><?php esc_html_e('Loading errors...', 'suggester'); ?></div>';
                    
                    // Make AJAX request
                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=suggester_load_api_errors_page&page=' + encodeURIComponent(page) + '&nonce=' + encodeURIComponent('<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>')
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            container.innerHTML = data.data.html;
                            container.classList.remove('loading');
                        } else {
                            container.innerHTML = '<div class="notice notice-error"><p><?php esc_html_e('Error:', 'suggester'); ?> ' + data.data + '</p></div>';
                            container.classList.remove('loading');
                        }
                    })
                    .catch(error => {
                        console.error('Pagination error:', error);
                        container.innerHTML = '<div class="notice notice-error"><p><?php esc_html_e('An error occurred while loading the page.', 'suggester'); ?></p></div>';
                        container.classList.remove('loading');
                    });
                }
                
                // Initialize pagination
                setupApiErrorsPagination();
                
                // Export API errors functionality
                const exportApiErrorsBtn = document.getElementById('export-api-errors');
                if (exportApiErrorsBtn) {
                    exportApiErrorsBtn.addEventListener('click', function() {
                        this.disabled = true;
                        this.textContent = '<?php esc_html_e('Exporting...', 'suggester'); ?>';
                        
                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=suggester_export_api_errors&_wpnonce=' + encodeURIComponent('<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>')
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Create and trigger download
                                const blob = new Blob([data.data.content], { type: 'text/csv' });
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = data.data.filename;
                                document.body.appendChild(a);
                                a.click();
                                window.URL.revokeObjectURL(url);
                                document.body.removeChild(a);
                                
                                alert('<?php esc_html_e('API errors exported successfully!', 'suggester'); ?>');
                            } else {
                                alert('<?php esc_html_e('Error:', 'suggester'); ?> ' + data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Export error:', error);
                            alert('<?php esc_html_e('An error occurred while exporting.', 'suggester'); ?>');
                        })
                        .finally(() => {
                            this.disabled = false;
                            this.textContent = '<?php esc_html_e('Export This Month\'s Errors (CSV)', 'suggester'); ?>';
                        });
                    });
                }
                
                // Clear API errors functionality
                const clearApiErrorsBtn = document.getElementById('clear-api-errors');
                if (clearApiErrorsBtn) {
                    clearApiErrorsBtn.addEventListener('click', function() {
                        if (!confirm('<?php esc_html_e('Are you sure you want to clear all API error logs? This action cannot be undone.', 'suggester'); ?>')) {
                            return;
                        }
                        
                        this.disabled = true;
                        this.textContent = '<?php esc_html_e('Clearing...', 'suggester'); ?>';
                        
                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=suggester_clear_api_errors&nonce=' + encodeURIComponent('<?php echo esc_attr(wp_create_nonce('suggester_clear_api_errors')); ?>')
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('<?php esc_html_e('All API error logs cleared successfully!', 'suggester'); ?>');
                                // Reload the API errors section
                                loadApiErrorsPage(1);
                            } else {
                                alert('<?php esc_html_e('Error:', 'suggester'); ?> ' + data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Clear error:', error);
                            alert('<?php esc_html_e('An error occurred while clearing logs.', 'suggester'); ?>');
                        })
                        .finally(() => {
                            this.disabled = false;
                            this.textContent = '<?php esc_html_e('Clear All API Errors', 'suggester'); ?>';
                        });
                    });
                }
            });
        </script>
        <?php
    }
    
    /**
     * Render API errors table (used for both initial load and AJAX pagination)
     *
     * @param array $api_errors Error data with pagination info
     * @param int $current_page Current page number
     */
    private function render_api_errors_table($api_errors, $current_page) {
        if (!empty($api_errors['errors'])) : ?>
            <div class="api-errors-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'suggester'); ?></th>
                            <th><?php esc_html_e('Tool Name', 'suggester'); ?></th>
                            <th><?php esc_html_e('Key Field Name', 'suggester'); ?></th>
                            <th><?php esc_html_e('Error Type', 'suggester'); ?></th>
                            <th><?php esc_html_e('Error Message', 'suggester'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($api_errors['errors'] as $error) : ?>
                            <tr>
                                <td><?php echo esc_html(gmdate('Y-m-d H:i', strtotime($error['created_at']))); ?></td>
                                <td><?php echo esc_html($error['tool_name'] ?: 'N/A'); ?></td>
                                <td><?php echo esc_html($error['key_field_name'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="error-type-badge error-type-<?php echo esc_attr($error['error_type']); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $error['error_type']))); ?>
                                    </span>
                                </td>
                                <td class="error-message-cell">
                                    <span class="error-message" title="<?php echo esc_attr($error['error_message']); ?>">
                                        <?php echo esc_html(wp_trim_words($error['error_message'], 10)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($api_errors['pages'] > 1) : ?>
                    <div class="api-errors-pagination">
                        <div class="pagination-info">
                            <p class="description">
                                <?php 
                                echo esc_html__('Showing page', 'suggester') . ' ' . esc_html($current_page) . ' ' . 
                                     esc_html__('of', 'suggester') . ' ' . esc_html($api_errors['pages']) . '. ' . 
                                     esc_html__('Total:', 'suggester') . ' ' . esc_html($api_errors['total']) . ' ' . 
                                     esc_html__('errors.', 'suggester');
                                ?>
                            </p>
                        </div>
                        
                        <div class="pagination-controls">
                            <!-- Previous button -->
                            <?php if ($current_page > 1) : ?>
                                <button type="button" class="button button-secondary" data-page="<?php echo esc_attr($current_page - 1); ?>">
                                    « <?php esc_html_e('Previous', 'suggester'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <!-- Page numbers -->
                            <span class="pagination-links">
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($api_errors['pages'], $current_page + 2);
                                
                                // Show first page if not in range
                                if ($start_page > 1) : ?>
                                    <button type="button" class="page-numbers" data-page="1">1</button>
                                    <?php if ($start_page > 2) : ?>
                                        <span class="page-numbers dots">…</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <!-- Page range -->
                                <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                                    <?php if ($i == $current_page) : ?>
                                        <span class="page-numbers current"><?php echo esc_html($i); ?></span>
                                    <?php else : ?>
                                        <button type="button" class="page-numbers" data-page="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></button>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <!-- Show last page if not in range -->
                                <?php if ($end_page < $api_errors['pages']) : ?>
                                    <?php if ($end_page < $api_errors['pages'] - 1) : ?>
                                        <span class="page-numbers dots">…</span>
                                    <?php endif; ?>
                                    <button type="button" class="page-numbers" data-page="<?php echo esc_attr($api_errors['pages']); ?>"><?php echo esc_html($api_errors['pages']); ?></button>
                                <?php endif; ?>
                            </span>
                            
                            <!-- Next button -->
                            <?php if ($current_page < $api_errors['pages']) : ?>
                                <button type="button" class="button button-secondary" data-page="<?php echo esc_attr($current_page + 1); ?>">
                                    <?php esc_html_e('Next', 'suggester'); ?> »
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="no-api-errors">
                <p><?php esc_html_e('No API errors recorded yet.', 'suggester'); ?></p>
            </div>
        <?php endif;
    }
    
    /**
     * AJAX handler for loading API errors page
     */
    public function ajax_load_api_errors_page() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $page = isset($_POST['page']) ? max(1, absint($_POST['page'])) : 1;
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wp_rest')) {
            wp_send_json_error('Security check failed');
        }
        
        $api_errors = $this->get_api_errors(5, $page);
        
        ob_start();
        $this->render_api_errors_table($api_errors, $page);
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'current_page' => $page,
            'total_pages' => $api_errors['pages'],
            'total_errors' => $api_errors['total']
        ));
    }
    
    /**
     * Get API errors for display
     *
     * @param int $limit Number of errors to retrieve
     * @param int $page Page number for pagination
     * @return array Array containing errors and pagination info
     */
    public function get_api_errors($limit = 5, $page = 1) {
        global $wpdb;
        
        if (!current_user_can('manage_options')) {
            return array('errors' => array(), 'total' => 0, 'pages' => 0);
        }
        
        $table_name = $wpdb->prefix . 'suggester_errors';
        $offset = ($page - 1) * $limit;
        
        // Get API key failure errors only
        $errors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE error_type = 'api_key_failure' ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A);
        
        // Get total count for pagination
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE error_type = 'api_key_failure'");
        
        return array(
            'errors' => $errors,
            'total' => $total_count,
            'pages' => ceil($total_count / $limit)
        );
    }
    
    /**
     * Export API errors as CSV
     */
    public function ajax_export_api_errors() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'suggester_errors';
        
        // Get all API errors for current month
        $start_of_month = current_time('Y-m') . '-01 00:00:00';
        $end_of_month = current_time('Y-m-t') . ' 23:59:59';
        
        $errors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE error_type = 'api_key_failure' AND created_at BETWEEN %s AND %s ORDER BY created_at DESC",
            $start_of_month,
            $end_of_month
        ), ARRAY_A);
        
        if (empty($errors)) {
            wp_send_json_error('No API errors found for this month');
        }
        
        // Generate CSV content
        $csv_content = "Date,Tool Name,Key Field Name,Error Type,Error Message\n";
        
        foreach ($errors as $error) {
            $csv_content .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $error['created_at'],
                str_replace('"', '""', $error['tool_name'] ?: 'N/A'),
                str_replace('"', '""', $error['key_field_name'] ?: 'N/A'),
                str_replace('"', '""', $error['error_type']),
                str_replace('"', '""', $error['error_message'])
            );
        }
        
        $filename = 'suggester-api-errors-' . current_time('Y-m') . '.csv';
        
        wp_send_json_success(array(
            'filename' => $filename,
            'content' => $csv_content
        ));
    }
    
    /**
     * Clear all API error logs
     */
    public function ajax_clear_api_errors() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'suggester_clear_api_errors')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'suggester_errors';
        
        $result = $wpdb->query("DELETE FROM $table_name WHERE error_type = 'api_key_failure'");
        
        if ($result !== false) {
            wp_send_json_success('All API error logs cleared successfully');
        } else {
            wp_send_json_error('Failed to clear API error logs');
        }
    }
} 
