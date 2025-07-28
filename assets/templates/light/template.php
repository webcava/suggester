<?php
/**
 * Light Template
 *
 * @package Suggester
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template data for Light
 */
function suggester_light_template() {
    return array(
        'name' => __('Light', 'suggester'),
        'description' => __('Light theme with blue accents', 'suggester'),
        'colors' => array('#ffffff', '#f5f8fa', '#1e88e5', '#0d47a1'),
        'config' => array(
            'primary_color' => '#ffffff',
            'secondary_color' => '#f5f8fa',
            'border_color' => '#e0e0e0',
            'accent_color' => '#1e88e5',
            'placeholder_color' => '#757575',
            'text_color' => '#333333',
            'card_color' => '#f5f8fa',
            /* Text size is now fixed */
            'show_favorites' => true,
        ),
        'thumbnail' => 'light-thumb.jpg'
    );
}

/**
 * Render Light Template HTML
 * 
 * @param array $settings Tool settings
 * @return string Template HTML
 */
function suggester_render_light_template($settings) {
    // Extract settings with defaults
    $config = isset($settings['template_config']) ? $settings['template_config'] : array();
    
    // Set default values if not provided
    $accent_color = isset($config['accent_color']) ? $config['accent_color'] : '#1e88e5';
    $show_favorites = isset($config['show_favorites']) ? (bool)$config['show_favorites'] : true;
    $button_text_color = isset($config['button_text_color']) ? $config['button_text_color'] : '#ffffff';
    
    ob_start();
    ?>
    <div class="suggester-container suggester-light" style="--accent-color: <?php echo esc_attr($accent_color); ?>; --button-text-color: <?php echo esc_attr($button_text_color); ?>;">
        <div class="suggester-input-section">
            <div class="suggester-input-wrapper">
                <input type="text" class="suggester-input" placeholder="<?php esc_attr_e('Describe what you want here...', 'suggester'); ?>" />
                <button class="suggester-submit-btn">
                    <?php esc_html_e('Suggest', 'suggester'); ?>
                </button>
            </div>
        </div>
        
        <?php if ($show_favorites) : ?>
        <div class="suggester-favorites-section">
            <div class="suggester-favorites-header">
                <span class="suggester-favorites-title"><?php esc_html_e('Suggestions you liked', 'suggester'); ?> (<span class="suggester-favorites-count">0</span>)</span>
                <span class="suggester-favorites-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </div>
            <div class="suggester-favorites-list" style="display: none;">
                <!-- Favorite items will be dynamically added here -->
                <div class="suggester-empty-favorites"><?php esc_html_e('No saved suggestions yet.', 'suggester'); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="suggester-results-section" style="display: none;">
            <div class="suggester-loading">
                <span class="suggester-loading-dot"></span>
                <span class="suggester-loading-dot"></span>
                <span class="suggester-loading-dot"></span>
            </div>
            <div class="suggester-results-list">
                <!-- Results will be dynamically added here -->
            </div>
        </div>
    </div>
    
    <!-- Template for suggestion card -->
    <template id="suggester-light-suggestion-template">
        <div class="suggester-suggestion-card">
            <div class="suggester-suggestion-accent-bar"></div>
            <div class="suggester-suggestion-content"></div>
            <div class="suggester-suggestion-actions">
                <span class="suggester-like-btn" title="<?php esc_attr_e('Save to favorites', 'suggester'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </span>
                <span class="suggester-copy-btn" title="<?php esc_attr_e('Copy to clipboard', 'suggester'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="copy-icon">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="check-icon" style="display: none;">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </span>
            </div>
        </div>
    </template>
    
    <!-- Template for favorite item -->
    <template id="suggester-light-favorite-template">
        <div class="suggester-favorite-item">
            <div class="suggester-favorite-content"></div>
            <span class="suggester-remove-favorite" title="<?php esc_attr_e('Remove from favorites', 'suggester'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </span>
        </div>
    </template>
    <?php
    
    return ob_get_clean();
} 