=== AI Alt Text Generator ===
Contributors: migkapa
Tags: images, alt text, AI, OpenAI, Anthropic, Claude, accessibility, SEO
Requires at least: 4.6
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 2.3.2
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

AI Alt Text Generator leverages the power of artificial intelligence to automatically generate clear and detailed descriptions for images, significantly enhancing website accessibility and SEO. Supporting both OpenAI (GPT-4o-mini) and Anthropic (Claude 3 Haiku) models for the most flexible and cost-effective alt text generation.

== Description ==
AI Alt Text Generator utilizes the power of leading AI providers including OpenAI (ChatGPT) and Anthropic (Claude) to automatically generate alt text for images on your WordPress site. This plugin connects to multiple AI APIs to provide intelligent and contextually relevant alt text, making your website more accessible and SEO-friendly.

**Key Features:**
- **Multi-Provider Support**: Choose between OpenAI and Anthropic AI providers
- **Cost-Effective Models**: Uses GPT-4o-mini and Claude 3 Haiku for optimal cost-efficiency
- **Bulk Processing**: Generate alt text for multiple images at once
- **Custom Prompts**: Customize the AI prompt to match your specific needs
- **Multi-Language Support**: Generate alt text in different languages
- **Testing Feature**: Test your prompts before applying them to images
- **Easy Integration**: Works seamlessly with WordPress media library
- **WP-CLI Support**: Configure providers and bulk-generate alt text from the command line

**WP-CLI:**
The plugin registers a `wp ai-alt-text` command suite, making it easy to automate alt text generation across one or many sites.

    # Configure a provider and API key
    wp ai-alt-text activate --provider=openai --key=sk-xxxxxxxx

    # Bulk-generate alt text for all images missing it
    wp ai-alt-text generate

    # Regenerate alt text for specific attachments
    wp ai-alt-text generate --ids=12,34,56 --force

    # Preview what would be processed without calling the API
    wp ai-alt-text generate --limit=20 --dry-run

    # Show current configuration and coverage
    wp ai-alt-text status

A typical install/activate/generate workflow:

    wp plugin install ai-alt-text-generator --activate
    wp ai-alt-text activate --provider=anthropic --key=sk-ant-xxxx
    wp ai-alt-text generate --yes

**New in Latest Version:**
- Added support for Anthropic (Claude) AI provider
- Enhanced provider switching capabilities
- Individual API key management for each provider
- Improved testing functionality
- Better error handling and user feedback

Important: This plugin requires external AI services (OpenAI and/or Anthropic) for its core functionality.

**AI Alt Text Generator Pro (optional upgrade)**

Need automation at scale? The optional Pro add-on builds on this free plugin with:
- WooCommerce product context for commerce-aware, SEO-rich alt text
- Scheduled background scans that describe new and existing images automatically
- A coverage analytics dashboard to track your progress toward 100%
- Automatic updates and priority support

Learn more at https://store.lessbutmore.ai — the free plugin remains fully functional on its own.

== Installation ==

1. Upload the plugin files to the /wp-content/plugins/ai-alt-text-generator directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Obtain an API key from your preferred provider:
   - **OpenAI**: Visit https://openai.com and sign up to get your API key
   - **Anthropic**: Visit https://console.anthropic.com and sign up to get your API key
4. Navigate to the 'Alt Text Generator' admin page in your WordPress dashboard.
5. Select your preferred AI provider and configure it with your API key.
6. Customize your prompt and language settings as needed.

== Frequently Asked Questions ==

= Does this plugin require an API key? =
Yes, you need an API key from either OpenAI or Anthropic (or both) to use this plugin. You can obtain these by signing up at their respective websites.

= Is there a Pro version? =
Yes. AI Alt Text Generator Pro is an optional add-on that adds WooCommerce product context, scheduled background scans that describe images automatically, a coverage analytics dashboard, automatic updates, and priority support. The free plugin is fully functional on its own. Learn more at https://store.lessbutmore.ai

= What AI providers are supported? =
The plugin currently supports:
- **OpenAI**: GPT-4o-mini, GPT-4o, and GPT-4 Vision Preview models
- **Anthropic**: Claude 3 Haiku, Claude 3.5 Sonnet, and Claude 3.7 Sonnet models

= Can I switch between different AI providers? =
Yes, you can easily switch between OpenAI and Anthropic providers in the plugin settings. Each provider has its own API key configuration.

= How does this plugin use the AI APIs? =
The plugin sends images to your selected AI provider's API, which then returns the generated alt text. This process requires an active internet connection and the transmission of data to the AI provider's servers.

= Can I generate alt text for multiple images at once? =
Yes, the AI Alt Text Generator supports bulk processing of images for efficient workflow.

= Can I use a custom prompt? =
Yes, you can customize the prompt used to generate alt text in the plugin settings. You can also test your prompts before applying them to images.

= Which provider is more cost-effective? =
Both providers offer cost-effective options. OpenAI's GPT-4o-mini and Anthropic's Claude 3 Haiku are optimized for cost efficiency while maintaining high quality output.

= Can I use the plugin from the command line (WP-CLI)? =
Yes. The plugin registers a `wp ai-alt-text` command suite with three subcommands:
- `wp ai-alt-text activate --provider=<openai|anthropic> --key=<api-key>` configures the provider and API key. Add `--skip-validation` to save without a live API check. (Each provider uses a fixed default model.)
- `wp ai-alt-text generate` bulk-generates alt text. Useful flags: `--limit=<n>`, `--provider=<provider>`, `--force` (regenerate existing), `--ids=12,34` (specific attachments), `--dry-run` (preview only), and `--yes` (skip confirmation).
- `wp ai-alt-text status` shows the active provider, which keys are configured, the prompt/language, and alt text coverage counts.

= Is my data secure? =
The plugin only sends image data and prompts to the selected AI provider for processing. Please review the privacy policies of OpenAI and/or Anthropic for details on how they handle data.

== Screenshots ==

https://lajmeshkurt.com/wp-content/uploads/2024/01/screenshot_1.png
https://lajmeshkurt.com/wp-content/uploads/2024/01/screenshot_2.png
https://lajmeshkurt.com/wp-content/uploads/2024/01/screenshot_3.png

== Changelog ==

= 2.3.2 =
- Introduced an optional Pro upgrade: WooCommerce product context, scheduled background scans, and a coverage analytics dashboard (https://store.lessbutmore.ai)
- Added contextual Pro information on the plugin's own settings page (shown only there; hidden when Pro is active)

= 2.3.1 =
- Updated the plugin homepage link
- Corrected the stable tag so the 2.3.x release is delivered to all sites

= 2.3.0 =
- Added developer extensibility hooks so companion add-ons can extend alt text generation without modifying the plugin
- New filters: aatg_providers, aatg_generate_provider, aatg_generate_prompt, aatg_generate_language, aatg_pre_generate_alt_text, aatg_generate_result, and aatg_alt_text
- New action: aatg_after_generate (fires after alt text is generated and saved)
- Introduced a shared aatg_save_generated_alt_text() helper used across the single, bulk, on-upload, REST, and WP-CLI paths
- No changes to existing behavior; tested up to WordPress 6.9

= 2.2.0 =
- Added WP-CLI support: `wp ai-alt-text activate`, `wp ai-alt-text generate`, and `wp ai-alt-text status`
- Bulk-generate alt text from the command line across many sites
- `activate` configures the AI provider and API key (with optional live key validation)
- `generate` supports --limit, --provider override, --force, --ids, --dry-run, and --yes flags
- `status` reports the active provider, configured keys (masked), prompt/language, and alt text coverage counts
- WordPress 6.8.1 compatibility maintained

= 2.1.2 =
- Removed debug logging statements for production release
- Cleaned up error_log calls throughout the codebase
- Improved performance by removing unnecessary logging overhead

= 2.1.1 =
- Restored language settings in admin interface
- Updated support information for WordPress Plugin Directory
- Fixed UI elements for better user experience
- Minor bug fixes and improvements

= 2.1 =
- Enhanced multi-provider support with OpenAI and Anthropic
- Improved provider factory architecture
- Better API key validation and error handling
- Enhanced testing capabilities
- WordPress 6.8.1 compatibility
- Optimized performance and reliability

= 2.0.71 =
- Added support for Anthropic (Claude) AI provider
- Implemented multi-provider architecture with provider factory system
- Enhanced settings with provider selection and individual API key management
- Added comprehensive testing functionality for prompts
- Improved error handling and user feedback
- Better backward compatibility with existing OpenAI configurations
- Enhanced bulk processing capabilities
- Added provider-specific help links and documentation

= 2.0.7 =
- Version adjustment

= 2.0.6 =
- Fixed single image generation while classic editor is active

= 2.0.5 =
- Added testing prompt to the plugin settings
- Improved error handling and feedback
- Fixed image processing in local environments
- Improved bulk processing

= 2.0.4 =
- Fixed admin page rendering issue

= 2.0.3 =
- Switched to GPT-4o-mini for cheaper and faster experience

= 2.0.2 =
- Fixed grid view not showing "Generate Alt Text" button

= 2.0.1 =
- Added the new GPT-4o model for 50% cheaper and faster experience
- Added custom prompt functionality
- Added option to choose language

= 1.0.0 =
- Initial release

== Upgrade Notice ==

= 2.2.0 =
Adds WP-CLI support: configure providers and bulk-generate alt text from the command line (wp ai-alt-text activate|generate|status).

== External Service Usage Disclosure ==

This plugin uses external AI services to generate alt text. Data (images and their metadata) is sent to your selected AI provider for processing.

**Supported Services:**
- **OpenAI**: For more information, please review the [OpenAI Terms of Use](https://openai.com/terms/) and [Privacy Policy](https://openai.com/privacy/)
- **Anthropic**: For more information, please review the [Anthropic Terms of Service](https://www.anthropic.com/terms) and [Privacy Policy](https://www.anthropic.com/privacy)

You can choose which service to use and are only required to agree to the terms of the service you select. The plugin does not store your images or generated alt text on our servers.

== Support ==

For support, feature requests, or bug reports, please contact us through the WordPress plugin support forum.
