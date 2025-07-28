=== Suggester ===
Contributors: webcava
Tags: ai, suggestions, content generator, gemini, openrouter
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.1
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An intelligent suggestion generator based on keywords using Google Gemini and OpenRouter APIs.

== Description ==

Suggester is a powerful WordPress plugin that allows site owners to create intelligent suggestion tools powered by AI. Using Google Gemini and OpenRouter APIs, the plugin generates content suggestions based on user-entered keywords.

= Key Features =

* Create up to 3 custom suggestion tools
* Each tool has its own shortcode for easy embedding
* Customizable prompts and output format
* Statistics tracking for usage
* Advanced API key management

= External Services =

This plugin connects to the following external services:

* Google Gemini API: Used to generate AI-powered suggestions. [Terms of Use](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy)
* OpenRouter API: Provides access to multiple AI models. [Terms of Use](https://openrouter.ai/terms), [Privacy Policy](https://openrouter.ai/privacy)

= How It Works =

1. The site owner creates a custom tool with a specific prompt template
2. The tool is embedded on any page using a shortcode (e.g., [suggester id='1'])
3. Visitors enter keywords in the tool's input field
4. The plugin sends the keywords and prompt to the AI service
5. Results are displayed instantly on the page

== Installation ==

1. Upload the `suggester` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Suggester dashboard to configure your API keys
4. Create your first suggestion tool
5. Use the provided shortcode to add the tool to any page

== Frequently Asked Questions ==

= How many tools can I create? =

The free version allows you to create up to 3 tools, each with its own unique shortcode.

= Do I need to have API keys to use this plugin? =

Yes, you need to have either a Google Gemini API key or an OpenRouter API key (or both) to use this plugin.

= What information is sent to the external APIs? =

The plugin sends the keyword entered by the user, along with the prompt template you've configured. No personal user data is sent to the APIs.

= Is this plugin compatible with multilingual sites? =

Yes, the plugin fully supports translation, including right-to-left (RTL) languages like Arabic.

== Screenshots ==

1. Dashboard Overview
2. Creating a new suggestion tool
3. Frontend tool display
4. Settings page

== Changelog ==

= 1.0.1 =
* New improvements

= 1.0.0 =
* Initial release

== Upgrade Notice ==
Please update to the latest version 1.0.1 to get more new features.

= 1.0.1 =
New improvements

== Privacy Policy ==

Suggester uses external AI services to generate suggestions. When a user enters a keyword, that keyword along with your configured prompt template is sent to either the Google Gemini API or OpenRouter API (depending on your settings). We do not collect or store personal information about your users, but the external services may have their own privacy policies regarding how they handle the data sent to them.

For more information about how these services handle data, please refer to their respective privacy policies:
* Google Gemini: https://policies.google.com/privacy
* OpenRouter: https://openrouter.ai/privacy 