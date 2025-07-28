<?php
/**
 * Activate Arabic Translation for Suggester Plugin
 * This script allows testing Arabic translation without changing WordPress language
 */

// WordPress environment
require_once('../../../wp-config.php');

// Check if we should activate Arabic
if (isset($_POST['activate_arabic'])) {
    // Force Arabic translation loading
    add_filter('suggester_force_arabic', '__return_true');
    $message = "Arabic translation activated for Suggester plugin!";
    $message_type = 'success';
} elseif (isset($_POST['test_translations'])) {
    // Test current translations
    $domain = 'suggester';
    
    // Try loading Arabic directly
    $mo_file = __DIR__ . '/languages/suggester-ar.mo';
    if (file_exists($mo_file)) {
        load_textdomain($domain, $mo_file);
    }
    
    $message = "Translation test completed. Check results below.";
    $message_type = 'info';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Suggester Arabic Translation Activator</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .test-results { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; margin: 20px 0; }
        .translation-item { margin: 10px 0; padding: 5px; border-left: 3px solid #007cba; padding-left: 10px; }
        .arabic { direction: rtl; text-align: right; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; margin: 5px; }
        button:hover { background: #005a87; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Suggester Arabic Translation Activator</h1>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo esc_attr($message_type); ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="test-results">
            <h2>Current Status</h2>
            <p><strong>WordPress Locale:</strong> <?php echo esc_html(get_locale()); ?></p>
            <p><strong>Is RTL:</strong> <?php echo esc_html(is_rtl() ? 'Yes' : 'No'); ?></p>
            <p><strong>Arabic MO File:</strong> <?php echo esc_html(file_exists(__DIR__ . '/languages/suggester-ar.mo') ? 'Found' : 'Missing'); ?></p>
            <?php if (file_exists(__DIR__ . '/languages/suggester-ar.mo')): ?>
                <p><strong>MO File Size:</strong> <?php echo esc_html(filesize(__DIR__ . '/languages/suggester-ar.mo')); ?> bytes</p>
            <?php endif; ?>
        </div>
        
        <form method="post">
            <button type="submit" name="activate_arabic" value="1">Activate Arabic for Suggester</button>
            <button type="submit" name="test_translations" value="1" class="btn-secondary">Test Current Translations</button>
        </form>
        
        <?php if (isset($_POST['test_translations']) || isset($_POST['activate_arabic'])): ?>
            <div class="test-results">
                <h2>Translation Test Results</h2>
                <?php
                $domain = 'suggester';
                $test_strings = array(
                    'Settings' => 'الإعدادات',
                    'Tools' => 'الأدوات', 
                    'Dashboard' => 'لوحة التحكم',
                    'Overview' => 'نظرة عامة',
                    'Help' => 'المساعدة',
                    'Generate Suggestions' => 'إنشاء اقتراحات',
                    'Keyword' => 'الكلمة المفتاحية',
                    'Language' => 'اللغة'
                );
                
                foreach ($test_strings as $english => $expected_arabic):
                    // Use individual translation calls with static strings for WordPress compliance
                    switch ($english) {
                        case 'Settings':
                            $translated = __('Settings', 'suggester');
                            break;
                        case 'Tools':
                            $translated = __('Tools', 'suggester');
                            break;
                        case 'Dashboard':
                            $translated = __('Dashboard', 'suggester');
                            break;
                        case 'Overview':
                            $translated = __('Overview', 'suggester');
                            break;
                        case 'Help':
                            $translated = __('Help', 'suggester');
                            break;
                        case 'Generate Suggestions':
                            $translated = __('Generate Suggestions', 'suggester');
                            break;
                        case 'Keyword':
                            $translated = __('Keyword', 'suggester');
                            break;
                        case 'Language':
                            $translated = __('Language', 'suggester');
                            break;
                        default:
                            $translated = $english;
                    }
                    $is_translated = ($translated !== $english);
                    $status_icon = $is_translated ? '✅' : '❌';
                ?>
                    <div class="translation-item">
                        <?php echo esc_html($status_icon); ?> 
                        <strong><?php echo esc_html($english); ?>:</strong>
                        <span class="arabic"><?php echo esc_html($translated); ?></span>
                        <?php if ($is_translated && $translated === $expected_arabic): ?>
                            <em>(Perfect match!)</em>
                        <?php elseif ($is_translated): ?>
                            <em>(Translated but different)</em>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="test-results">
            <h2>Instructions for WordPress Admin</h2>
            <p>To set your WordPress admin to Arabic:</p>
            <ol>
                <li>Go to <strong>Settings → General</strong> in WordPress admin</li>
                <li>Change <strong>Site Language</strong> to <strong>العربية (Arabic)</strong></li>
                <li>Save changes and refresh the Suggester plugin pages</li>
            </ol>
            
            <p>Or add this to your theme's functions.php to force Arabic for Suggester only:</p>
            <code style="background: #f1f1f1; padding: 10px; display: block; margin: 10px 0;">
                add_action('init', 'suggester_enable_arabic_mode');
            </code>
        </div>
        
        <p><a href="<?php echo esc_url(admin_url('admin.php?page=suggester')); ?>">← Back to Suggester Dashboard</a></p>
    </div>
</body>
</html> 