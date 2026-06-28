=== AI Alt Text Generator ===
Contributors: migkapa
Tags: alt text, accessibility, image seo, wcag, ai
Requires at least: 4.6
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 2.5.1
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Automatically generate WCAG-friendly image alt text with AI (OpenAI & Anthropic) to boost accessibility compliance (ADA/EAA) and image SEO.

== Description ==
AI Alt Text Generator automatically writes clear, descriptive alt text for every image on your WordPress site — improving **accessibility compliance** (WCAG 2.2, ADA, Section 508, and the European Accessibility Act) and **image SEO** at the same time. It uses leading vision AI from OpenAI and Anthropic with your own API key, so generation is transparent and at-cost with no per-image fees or vendor lock-in. You choose the provider and model — the plugin ships with a fast, low-cost default for each.

Alt text is required for accessible, legally compliant websites, and it helps search engines understand your images. But writing it by hand across an entire media library rarely happens — so this plugin does it for you, in bulk or automatically on upload.

It produces concise, **WCAG-aligned** descriptions (no "image of…" filler, sensible length) and can fold in the **page context** and your **SEO focus keyphrase** for sharper, more relevant alt text. It pairs perfectly with accessibility audit tools that flag missing alt text — this is the plugin that fills the gaps.

**Key Features:**
- **Accessibility & compliance**: WCAG-aligned output to support ADA, EAA, and Section 508 requirements
- **SEO keyphrase integration**: automatically weaves in focus keyphrases from Yoast SEO, Rank Math, and SEOPress (without keyword stuffing)
- **Page-context aware**: uses the page/post the image belongs to for more relevant descriptions
- **Multi-Provider Support**: choose between OpenAI and Anthropic — your own API key, no lock-in
- **Cost-Effective by default**: ships with a fast, low-cost vision model for each provider — and you can switch to any model your provider offers
- **Bulk Processing**: generate alt text for your whole library at once, or automatically on upload
- **Custom Prompts**: tailor the AI prompt to your brand and needs
- **Multi-Language Support**: generate alt text in many languages
- **Testing Feature**: preview prompts before applying them to images
- **WP-CLI Support**: configure providers and bulk-generate from the command line
- **Developer-friendly**: extensible via action/filter hooks for custom integrations and add-ons

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
- Optional managed-credit mode — generate alt text with no API key needed (free tier included)
- WCAG-aligned output, SEO focus-keyphrase integration (Yoast / Rank Math / SEOPress), and page-context awareness
- Optionally set the image Title, Caption, and Description from the generated alt text
- Future-proof model handling (no hard-coded model versions; configurable defaults)

Important: This plugin uses external AI services (your own OpenAI/Anthropic key, or the optional managed-credit service) to generate alt text.

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
You have two options: use your own OpenAI or Anthropic API key (free — you pay the provider directly at cost), or use the optional managed-credit service that needs no API key at all (with a free tier). Either way works.

= Can I use it without an API key? =
Yes. Enable "managed credits" in the plugin settings to generate alt text without any API key — the free tier includes 50 images per month, and you can upgrade for more. Learn more at https://store.lessbutmore.ai

= Is there a Pro version? =
Yes. AI Alt Text Generator Pro is an optional add-on that adds WooCommerce product context, scheduled background scans that describe images automatically, a coverage analytics dashboard, automatic updates, and priority support. The free plugin is fully functional on its own. Learn more at https://store.lessbutmore.ai

= What AI providers are supported? =
You bring your own API key for **OpenAI** or **Anthropic**, and you can use any vision-capable model your provider offers. The plugin ships with a sensible, low-cost default for each provider and lets you change the model anytime in settings — so you're never locked to a specific model if providers add or retire one.

= Can I switch between different AI providers? =
Yes, you can easily switch between OpenAI and Anthropic providers in the plugin settings. Each provider has its own API key configuration.

= How does this plugin use the AI APIs? =
The plugin sends images to your selected AI provider's API, which then returns the generated alt text. This process requires an active internet connection and the transmission of data to the AI provider's servers.

= Can I generate alt text for multiple images at once? =
Yes, the AI Alt Text Generator supports bulk processing of images for efficient workflow.

= Can I use a custom prompt? =
Yes, you can customize the prompt used to generate alt text in the plugin settings. You can also test your prompts before applying them to images.

= Which provider is more cost-effective? =
Both providers offer fast, low-cost vision models, and the plugin defaults to a cost-efficient model for each. You can switch models anytime in settings to balance cost and quality.

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

= 2.5.1 =
- New: optional **managed credits** mode — generate alt text with no API key needed (free 50 images/month via the AI Alt Text Generator service). Bring-your-own-key remains fully supported and free.
- Future-proofed model handling: no hard-coded model versions; the default model is filterable (aatg_default_model), and the deprecated GPT-4 Vision Preview was removed.
- Connect and manage a managed-credits account from the General settings tab.

= 2.5.0 =
- New: optionally also set the image Title, Caption, and Description from the generated alt text (toggles on the settings page)
- Expanded language support from 13 to ~48 languages
- Settings UI refinements

= 2.4.1 =
- Fix: the "Upgrade to Pro" notice on the settings page is now fully visible (moved to the standard admin notice area)

= 2.4.0 =
- WCAG-aligned alt text by default: concise, purpose-driven output with no "image of" filler and a sensible length, better for screen readers and SEO
- SEO focus-keyphrase integration: automatically incorporates the keyphrase from Yoast SEO, Rank Math, and SEOPress (filterable; no keyword stuffing)
- Page-context awareness: uses the title of the page/post an image belongs to for more relevant descriptions
- Higher-quality, lower-cost image sampling: sends an appropriately sized image instead of a tiny thumbnail or the full-size original
- Refreshed accessibility-first positioning (WCAG 2.2 / ADA / EAA)

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
