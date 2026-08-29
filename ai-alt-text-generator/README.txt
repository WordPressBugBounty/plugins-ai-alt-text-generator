=== AI Alt Text Generator ===
Contributors: migkapa
Tags: alt text, accessibility, image alt text, wcag, image seo
Requires at least: 4.6
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 2.7.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Bulk-generate WCAG-friendly image alt text with AI — no API key needed. Every site gets free monthly credits for accessibility (ADA/EAA compliance) and image SEO.

== Description ==
AI Alt Text Generator writes clear, descriptive alt text for every image on your WordPress site — improving **accessibility compliance** (WCAG 2.2, ADA, Section 508, and the European Accessibility Act) and **image SEO** at the same time. It works out of the box: activate the plugin and start generating, **no API key and no signup required**. Every site gets **50 free images per month** through the built-in managed credits service.

That makes it a fit for small organizations that need accessible, legally compliant websites but don't want to wrangle developer accounts or AI billing — nonprofits, churches, schools, clinics, and small businesses working toward WCAG / EU Accessibility Act compliance.

Alt text is required for accessible, legally compliant websites, and it helps search engines understand your images. But writing it by hand across an entire media library rarely happens — so this plugin does it for you, in bulk or automatically on upload. It produces concise, **WCAG-aligned** descriptions (no "image of…" filler, sensible length) and can fold in the **page context** and your **SEO focus keyphrase** for sharper, more relevant alt text. It pairs perfectly with accessibility audit tools that flag missing alt text — this is the plugin that fills the gaps.

**How it works — two ways to generate:**

1. **Managed credits (default, recommended)** — No API key, no signup. The plugin connects to the AI Alt Text Generator managed service automatically and gives every site **50 free images per month**. Need more? Affordable paid credit plans are available. This uses an external, account-based service (see the disclosure below) and is optional beyond the free tier.
2. **Bring your own API key (advanced)** — Prefer to run on your own OpenAI or Anthropic account? Add your own API key in the Advanced settings and generation runs at-cost, directly with your provider, with no per-image fees or vendor lock-in. You choose the provider and model.

**Key Features:**
- **Works out of the box**: no API key needed — 50 free images per month on every site
- **Accessibility & compliance**: WCAG-aligned output to support ADA, EAA, and Section 508 requirements
- **SEO keyphrase integration**: automatically weaves in focus keyphrases from Yoast SEO, Rank Math, and SEOPress (without keyword stuffing)
- **Page-context aware**: uses the page/post the image belongs to for more relevant descriptions
- **Bring your own key (optional)**: advanced users can plug in their own OpenAI or Anthropic key — any vision-capable model, no lock-in
- **Bulk Processing**: generate alt text for your whole library at once, or automatically on upload
- **Custom Prompts**: tailor the AI prompt to your brand and needs
- **Multi-Language Support**: generate alt text in many languages
- **Testing Feature**: preview prompts before applying them to images
- **WP-CLI Support**: bulk-generate from the command line
- **Developer-friendly**: extensible via action/filter hooks for custom integrations and add-ons

**WP-CLI:**
The plugin registers a `wp ai-alt-text` command suite, making it easy to automate alt text generation across one or many sites.

    # Bulk-generate alt text for all images missing it (uses managed credits by default)
    wp ai-alt-text generate

    # Optional: configure your own provider and API key (advanced)
    wp ai-alt-text activate --provider=openai --key=sk-xxxxxxxx

    # Regenerate alt text for specific attachments
    wp ai-alt-text generate --ids=12,34,56 --force

    # Preview what would be processed without calling the API
    wp ai-alt-text generate --limit=20 --dry-run

    # Show current configuration and coverage
    wp ai-alt-text status

A typical install-and-generate workflow needs no key at all:

    wp plugin install ai-alt-text-generator --activate
    wp ai-alt-text generate --yes

**New in Latest Version:**
- Managed credits are now the default: generate alt text with no API key and no signup — 50 free images per month on every site
- Bring-your-own OpenAI/Anthropic key moved to an "Advanced" option for users who prefer to run on their own account
- WCAG-aligned output, SEO focus-keyphrase integration (Yoast / Rank Math / SEOPress), and page-context awareness
- Optionally set the image Title, Caption, and Description from the generated alt text
- Future-proof model handling (no hard-coded model versions; configurable defaults)

Important: This plugin uses an external AI service to generate alt text — either the optional managed-credit service (account-based, free tier plus paid plans) or your own OpenAI/Anthropic key. See the External Service Usage Disclosure below.

**AI Alt Text Generator Pro (optional upgrade)**

Need automation at scale? The optional Pro add-on builds on this free plugin with:
- WooCommerce product context for commerce-aware, SEO-rich alt text
- Scheduled background scans that describe new and existing images automatically
- A coverage analytics dashboard to track your progress toward 100%
- Automatic updates and priority support

[Learn more about Pro](https://store.lessbutmore.ai?utm_source=plugin&utm_medium=readme&utm_campaign=pro) — the free plugin remains fully functional on its own.

== Installation ==

1. Upload the plugin files to the /wp-content/plugins/ai-alt-text-generator directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. That's it — the plugin connects to the managed credits service automatically and you can start generating right away, with **no API key and no signup**. Every site gets 50 free images per month.
4. Navigate to the 'Alt Text Generator' admin page, then use "Generate" on a single image or bulk-generate across your library.
5. (Optional, advanced) Prefer your own AI account? Open the Advanced settings and add an API key:
   - **OpenAI**: Visit https://openai.com and sign up to get your API key
   - **Anthropic**: Visit https://console.anthropic.com and sign up to get your API key
6. Customize your prompt and language settings as needed.

== Frequently Asked Questions ==

= Do I need an API key to use this plugin? =
No. The plugin works out of the box with the built-in managed credits service — no API key and no signup. Every site gets 50 free images per month. If you'd rather run on your own AI account, you can optionally add your own OpenAI or Anthropic API key in the Advanced settings.

= What are managed credits and are they free? =
Managed credits let the plugin generate alt text through the AI Alt Text Generator managed service without you configuring anything. Every site gets **50 free images per month**. If you need more, affordable paid credit plans are available. This uses an external, account-based service and is optional beyond the free tier. [Learn more about managed credits](https://store.lessbutmore.ai?utm_source=plugin&utm_medium=readme&utm_campaign=managed_credits).

= Can I use my own OpenAI or Anthropic API key instead? =
Yes. In the Advanced settings you can add your own OpenAI or Anthropic API key and generate directly on your own account — at cost, with no per-image fees and no vendor lock-in. You can use any vision-capable model your provider offers; the plugin ships with a sensible, low-cost default and lets you change the model anytime.

= Is this good for accessibility and legal compliance? =
Yes — that's the point. It produces WCAG-aligned alt text to help you meet WCAG 2.2, ADA, Section 508, and European Accessibility Act (EAA) requirements. It's a practical fit for nonprofits, churches, schools, and small businesses that need accessible websites without a developer on staff.

= Is there a Pro version? =
Yes. AI Alt Text Generator Pro is an optional add-on that adds WooCommerce product context, scheduled background scans that describe images automatically, a coverage analytics dashboard, automatic updates, and priority support. The free plugin is fully functional on its own. [Learn more about Pro](https://store.lessbutmore.ai?utm_source=plugin&utm_medium=readme&utm_campaign=pro).

= How does this plugin use the AI service? =
The plugin sends images to the AI service — the managed credits service by default, or your selected provider's API if you've added your own key — which returns the generated alt text. This process requires an active internet connection and the transmission of image data to the service's servers.

= Can I generate alt text for multiple images at once? =
Yes, the AI Alt Text Generator supports bulk processing of images for efficient workflow, and can also generate automatically on upload.

= How do I stop it overwriting alt text I wrote myself? =
Under Bulk Generation, "Never overwrite alt text containing" takes a comma-separated list of words — "logo, sponsor, headshot", for example. Any image whose current alt text contains one of them is left exactly as it is, even with "Process All Images" turned on, and costs nothing to keep. Press Save (or Start, which saves first), then use the Testing tab to select an image and confirm it says "Protected" before you run.

= Can I use a custom prompt? =
Yes, you can customize the prompt used to generate alt text in the plugin settings. You can also test your prompts before applying them to images.

= Can I use the plugin from the command line (WP-CLI)? =
Yes. The plugin registers a `wp ai-alt-text` command suite:
- `wp ai-alt-text generate` bulk-generates alt text (using managed credits by default). Useful flags: `--limit=<n>`, `--provider=<provider>`, `--force` (regenerate existing), `--ids=12,34` (specific attachments), `--dry-run` (preview only), and `--yes` (skip confirmation).
- `wp ai-alt-text activate --provider=<openai|anthropic> --key=<api-key>` optionally configures your own provider and API key. Add `--skip-validation` to save without a live API check.
- `wp ai-alt-text status` shows the active mode/provider, which keys are configured, the prompt/language, and alt text coverage counts.

= Is my data secure? =
The plugin only sends image data and prompts to the AI service for processing to produce alt text. Images and generated alt text are not stored on our servers beyond what is needed to return your result. If you use your own API key, please review the privacy policies of OpenAI and/or Anthropic. For the managed service, see the External Service Usage Disclosure below.

== Screenshots ==

https://lajmeshkurt.com/wp-content/uploads/2024/01/screenshot_1.png
https://lajmeshkurt.com/wp-content/uploads/2024/01/screenshot_2.png
https://lajmeshkurt.com/wp-content/uploads/2024/01/screenshot_3.png

== Changelog ==

= 2.7.0 =
- **Fixed: the "never overwrite alt text containing" list did nothing.** The field added in 2.6.0 sits on the Bulk Generation screen, and that screen had no Save control at all — so the words you typed never reached the server, and a bulk run replaced the alt text they were meant to protect. The screen now has its own Save button and a clear "unsaved changes" marker, and pressing Start saves any pending change before the run begins. Reported on the support forum by a user who lost hand-written alt text to it; sorry, and thank you for pushing on it.
- **Improved: protected images no longer cost a credit.** The keep-words check now runs *before* the image is sent to the AI, instead of throwing the answer away afterwards. Keeping your own alt text is free and bulk runs finish faster. When a run ends, it tells you how many images kept their existing text.
- **New: check an image before you run.** The Testing tab now shows an image's current alt text and says plainly whether a bulk run would keep it or replace it — no generation, no credit spent.
- **Fixed: "Bulk generation completed!" could appear while a run was still going.** The two-second progress poll raced the start of the run and could read "0 of 0 images" as a finished one.
- **Fixed: the Testing tab was unusable on managed credits.** It asked for an API key that managed-credit sites (the default) don't have, leaving "Generate Alt Text" disabled with a misleading message.
- **Fixed: keep-words now match accented capitals**, so a list containing "Löwe" also protects "LÖWE im Zoo".
- **Fixed: a failed settings save no longer blanks the settings screen.**
- Tested up to WordPress 7.1.

= 2.6.8 =
- **Fixed: "Unsupported parameter: 'max_tokens' is not supported with this model" when using GPT-5.** GPT-5 (and OpenAI's o-series) require `max_completion_tokens` instead of `max_tokens` and reject a custom temperature, so every generation failed for anyone who picked a GPT-5 model from the dropdown. The plugin now sends the request shape each model family expects, in the Media Library, bulk runs, WP-CLI, and the block editor button alike.
- **Fixed: GPT-5 could return empty alt text.** Reasoning models spend part of their token budget on hidden reasoning, which the old 100-token cap could swallow whole. The budget is now sized for that, and if a model still runs out the error says so instead of "Invalid response from OpenAI API".
- **Improved: an unrecognised model no longer fails the whole run.** If OpenAI rejects one optional request parameter, the plugin retries without it, so a model family released after this version still works.

= 2.6.7 =
- **Fixed: the "out of credits" banner stayed up after buying credits.** It only cleared on the next successful generation, so a site that had just paid for a top-up still saw "out of credits" on the dashboard until it ran again. The plugin now re-checks the balance with the store (at most every 5 minutes) while the banner is showing and removes it as soon as credits are back.
- **Improved: top-up credits are shown separately on the settings page** ("4,917 credits left (0/50 plan + 4,917 top-up)") instead of an odd-looking "4917/50".

= 2.6.6 =
- **Fixed: bulk generation retries provider failures instead of losing the image.** A rate limit or provider timeout during a bulk run used to skip that image permanently on its first failure — one site lost 86 of 115 images to a single rate-limited hour. The runner now retries each failed image up to 3 times with a growing pause before moving on.
- **Fixed: bulk runs could silently skip images uploaded in the same second.** The processing queue had no fixed order, so on libraries where many images share an upload time (any imported library), paging could walk past some images and revisit others. The queue is now processed in a fixed order.

= 2.6.5 =
- **Fixed: switching the AI Provider in settings never saved.** Changing the provider and pressing Save Changes silently kept the old provider — the select looked switched but the stored setting was unchanged, so Anthropic was unreachable through the UI. Thanks to the detailed community bug report for pinpointing this.
- **Fixed: Anthropic could not be used at all.** Every bundled Claude model had been retired by Anthropic, and because key validation generated a test message with one of them, a perfectly valid API key reported "Failed to validate API key." Validation no longer depends on any particular model, and the bundled lists now use stable model aliases (claude-sonnet-5, claude-haiku-4-5, claude-opus-5) that keep working when new versions ship.
- **Fixed: the Model setting was saved but never used.** Both providers always generated with a hardcoded default; the model you chose is now actually sent with each request.
- **New: the Model field is a real dropdown of models your API key can use right now**, fetched live from your provider (cached 12 hours, with a refresh button). If a saved model is ever retired again, the plugin automatically falls back to a current default instead of failing on every image — and the settings screen tells you.
- **Changed: the OpenAI default model is now gpt-4o instead of gpt-4o-mini.** On image inputs OpenAI's mini tier bills roughly 20x the image tokens, making gpt-4o-mini both more expensive per image and noticeably worse at alt text. Your saved model choice is respected either way.

= 2.6.4 =
- **Performance: the plugin no longer loads any files on your site's public pages.** Leftover boilerplate enqueued an empty stylesheet and an empty script (which also pulled in jQuery as a dependency) on every visitor-facing page. All of the plugin's work happens in wp-admin, so these served no purpose — they have been removed, saving two requests on every page load. Thanks to the user who reported this.

= 2.6.3 =
- **New: the plugin now tells you when it has run out of credits.** Previously, if a bulk run hit the monthly limit overnight — or you navigated away mid-run — nothing said so afterwards, and new uploads quietly stopped getting alt text. A notice now appears in your dashboard and Media Library explaining that generation is paused, how long it has been paused, and how to add credits. It disappears on its own the moment credits are available again, and can be snoozed for a week.

= 2.6.2 =
- **Fixed: bulk generation could die a few images into a large run.** A single failed request — a hosting timeout, a momentary 500, a burst limit on admin requests — used to kill the whole run silently. Failed requests are now retried up to 3 times with growing pauses, and if the run still has to stop, it tells you at which image, why, and that pressing Start resumes where it left off.
- **Fixed: bulk generation sent full-size original images to the AI provider.** Very large originals (big photography uploads) could exhaust PHP memory or time limits and break the run at a consistent point. It now sends a resized rendition — faster, lighter on the server, cheaper on your API key, and just as accurate.
- **Fixed: the "images without alt text" bulk run from the settings page could skip images and finish early.** The same shrinking-queue bug fixed for background runs in 2.5.5 also affected the settings-page runner; a 500-image run could silently miss up to half the library. Both runners now use the same corrected paging.
- The completion message now reports how many images were skipped due to errors instead of always claiming full success.

= 2.6.1 =
- **Fixed: bulk generation no longer keeps retrying after your credits run out.** It used to treat "out of credits" like a single bad image and carry on to the next one, so a large library produced hundreds of failed attempts in a row while the progress bar told you nothing. It now stops as soon as it hits an account-level limit.
- Bulk generation now explains why it stopped — out of credits, account not activated, or a connection problem — and tells you how many images it finished before stopping. Everything generated up to that point is saved.
- The same rule applies to the Media Library bulk action running in the background.

= 2.6.0 =
- **New: protect alt text you wrote yourself.** Under Bulk Generation, list words such as "logo" or "sponsor" and any image whose current alt text contains one of them is left untouched — even with "Process All Images" turned on. Matching ignores case and works inside longer words, so "logo" also protects "Company logo".
- The new rule applies everywhere alt text is saved: bulk runs, single images, on upload, and WP-CLI.

= 2.5.5 =
- **Fixed: the Media Library "Generate Alt Text" bulk action only ever processed the first selected image.** The background job that handles the rest exited immediately because the bulk action never flagged a run as started, so the remaining images were silently dropped.
- **Fixed: bulk generation ignored your selection.** Once the background job did run, it worked through the whole media library instead of the images you picked. It now processes exactly the selection.
- **Fixed: bulk runs skipped images.** When generating for "images without alt text", the queue shrank as images were processed while the read position kept advancing, so a large run could silently miss a substantial share of the library.
- Fixed: an image that failed to generate could stall a bulk run instead of being skipped.
- Bulk generation now applies the Title / Caption / Description settings and add-on filters, matching single-image generation.
- The Media Library bulk action now reports what happened (started, nothing selected, or a run already in progress) instead of redirecting silently.

= 2.5.4 =
- **Fixed: managed credits now actually generate alt text.** A result-format mismatch caused managed-mode generation to fail on every path (bulk, single, upload, WP-CLI). This was the root cause behind "nothing happens" reports in managed mode.
- **Works out of the box**: new installs connect to managed credits automatically and get 50 free images per month — no API key, no signup, no email required.
- Bulk Generate now works in managed-credit mode (the Start button no longer requires an API key).
- Clear, actionable errors when credits run out, an account needs attention, or the service is unreachable — instead of silent failures.
- New: alt-text coverage dashboard widget showing how many images still need alt text.
- Low-credit warning in settings so you know before you run out.
- Already have a paid plan? Paste your API token from your dashboard to connect this site.
- Accessibility framing updated for WCAG 1.1.1 and the European Accessibility Act.

= 2.5.3 =
- Settings: managed credits (no API key needed) is now the recommended option, shown first; using your own API key is presented as an "Advanced" option below

= 2.5.2 =
- Fix: the "Upgrade to Pro" panel now renders inside the settings screen so it's always fully visible (the previous admin-notice version could be covered by the settings header)

= 2.5.1 =
- New: optional **managed credits** mode — generate alt text with no API key needed (free monthly images via the AI Alt Text Generator service). Bring-your-own-key remains fully supported and free.
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

= 2.7.0 =
The "never overwrite alt text containing" list added in 2.6.0 never saved, so bulk runs overwrote the alt text it was supposed to protect. Fixed — update before your next bulk run.

= 2.6.8 =
Fixes generation failing with "Unsupported parameter: 'max_tokens'" on GPT-5 models. Update if you selected GPT-5, GPT-5 Mini, or an o-series model.

= 2.5.3 =
Managed credits are now the default — generate WCAG-friendly alt text with no API key and no signup, 50 free images/month on every site. Bring-your-own key is still available as an advanced option.

= 2.2.0 =
Adds WP-CLI support: configure providers and bulk-generate alt text from the command line (wp ai-alt-text activate|generate|status).

== External Service Usage Disclosure ==

This plugin relies on external AI services to generate alt text. In all cases, data (images and their metadata) is sent to an external service for processing. The plugin does not store your images or generated alt text on our servers beyond what is needed to return your result.

**Managed credits service (default)**
By default the plugin uses the AI Alt Text Generator managed credits service so it works with no API key. On activation, the plugin automatically connects your site to obtain a free account (50 images per month) — this registers your site with the external service and requires an account. Images to be described are sent to this service for processing. Use beyond the free monthly tier requires a paid credit plan. The service is operated via https://store.lessbutmore.ai?utm_source=plugin&utm_medium=readme&utm_campaign=disclosure — please review the site for its terms and privacy policy. You can disable managed credits in the plugin settings and use your own API key instead.

**Bring-your-own-key providers (optional, advanced)**
If you add your own API key, images are sent to the provider you select:
- **OpenAI**: For more information, please review the [OpenAI Terms of Use](https://openai.com/terms/) and [Privacy Policy](https://openai.com/privacy/)
- **Anthropic**: For more information, please review the [Anthropic Terms of Service](https://www.anthropic.com/terms) and [Privacy Policy](https://www.anthropic.com/privacy)

You choose which path to use and are only required to agree to the terms of the service you actually use.

== Support ==

For support, feature requests, or bug reports, please contact us through the WordPress plugin support forum.
