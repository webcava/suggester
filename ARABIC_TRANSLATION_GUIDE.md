# Suggester Plugin - Arabic Translation Guide

## Overview
The Suggester plugin now includes complete Arabic translation with RTL (Right-to-Left) support. This guide explains multiple ways to enable Arabic translation in the plugin.

## Current Status ✅
- ✅ Complete Arabic translation (131 strings, 100% coverage)
- ✅ Professional Modern Standard Arabic translations
- ✅ RTL support for admin interface and frontend
- ✅ Dynamic RTL detection and layout adaptation
- ✅ Mobile-responsive RTL design

## Translation Files
- **PO File**: `languages/suggester-ar.po` (17.6KB)
- **MO File**: `languages/suggester-ar.mo` (15.2KB) 
- **Coverage**: 131/131 strings (100%)

## Method 1: WordPress Site Language (Recommended)

### Steps:
1. Go to WordPress Admin → **Settings** → **General**
2. Change **Site Language** to **العربية (Arabic)**
3. Click **Save Changes**
4. Visit any Suggester plugin page

### Result:
- Entire WordPress admin will be in Arabic
- Suggester plugin will automatically display in Arabic
- RTL layout will be activated automatically

## Method 2: Plugin-Specific Arabic Mode

### Option A: Add to functions.php
Add this code to your theme's `functions.php` file:

```php
// Force Arabic translation for Suggester plugin only
add_action('init', 'suggester_enable_arabic_mode');
```

### Option B: URL Parameter
Add `?suggester_lang=ar` to any Suggester admin page URL:
```
http://yoursite.com/wp-admin/admin.php?page=suggester&suggester_lang=ar
```

### Result:
- Only Suggester plugin displays in Arabic
- Rest of WordPress remains in your current language
- RTL layout activated for Suggester pages only

## Method 3: Temporary Testing

### Use the Arabic Activator Tool:
Visit: `http://yoursite.com/wp-content/plugins/suggester/activate-arabic.php`

This tool allows you to:
- Test current translation status
- Temporarily activate Arabic mode
- View translation test results
- Get troubleshooting information

## Method 4: Manual Force Loading

### For developers/advanced users:
Add this code to test Arabic translation loading:

```php
// Force load Arabic translation
$domain = 'suggester';
$mo_file = WP_PLUGIN_DIR . '/suggester/languages/suggester-ar.mo';
if (file_exists($mo_file)) {
    load_textdomain($domain, $mo_file);
}
```

## Troubleshooting

### If Arabic translation isn't working:

1. **Check file permissions**: Ensure the `languages/` directory and MO file are readable
2. **Verify MO file**: Should be 15,177 bytes with magic bytes `950412de`
3. **Clear cache**: If using caching plugins, clear all caches
4. **Check WordPress locale**: Current locale should contain 'ar' for automatic detection

### Debug Information:
Enable WordPress debug mode by adding to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Then check `/wp-content/debug.log` for Suggester translation loading messages.

## Features in Arabic Mode

### Admin Interface:
- All menu items translated
- Forms and input labels in Arabic
- Error messages and notifications in Arabic
- Help text and descriptions in Arabic
- RTL layout for proper text flow

### Frontend (Shortcodes):
- Suggestion form labels in Arabic
- Button text in Arabic
- Error/success messages in Arabic
- RTL layout for Arabic content

### Technical Features:
- Automatic RTL detection for 11 languages
- CSS RTL support for all screen sizes
- Mobile-responsive RTL design
- Proper Arabic typography support

## Supported Arabic Locales
The plugin supports these Arabic locale variants:
- `ar` (Standard Arabic)
- `ar_SA` (Saudi Arabia)
- `ar_EG` (Egypt)
- `ar_AE` (United Arab Emirates)
- And other Arabic locale variants

## Testing

### Quick Test:
1. Visit: `http://yoursite.com/wp-content/plugins/suggester/activate-arabic.php`
2. Click "Test Current Translations"
3. Verify that strings like "Settings" show as "الإعدادات"

### Full Test:
1. Enable Arabic using any method above
2. Visit Suggester admin pages
3. Check that all interface elements are in Arabic
4. Verify RTL layout is applied
5. Test frontend shortcodes

## Support

If you encounter issues with Arabic translation:

1. Try the troubleshooting steps above
2. Use the Arabic Activator tool for diagnostics
3. Check WordPress debug logs
4. Verify all files are properly uploaded

## File Verification

### Expected file sizes:
- `suggester-ar.po`: ~17.6KB
- `suggester-ar.mo`: ~15.2KB (exactly 15,177 bytes)

### MO file verification:
The MO file should start with magic bytes `950412de` (little-endian) or `de120495` (big-endian).

---

**Last Updated**: May 29, 2025  
**Translation Coverage**: 131/131 strings (100%)  
**RTL Support**: Complete  
**WordPress Compatibility**: 5.0+ 