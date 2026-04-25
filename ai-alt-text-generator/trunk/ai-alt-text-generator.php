<?php
/**
 * Plugin Name:       AI Alt Text Generator
 * Plugin URI:        https://aialttextgenerator.com/
 * Description:       Uses chatGPT ( GPT-4o-mini ) to automatically create descriptive alt text for your images, improving accessibility and SEO effortlessly.
 * Version:           2.1.2
 * Author:            migkapa
 * Author URI:        https://github.com/migkapa
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ai-alt-text-generator
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'AATG_TEXT_GENERATOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'AATG_TEXT_GENERATOR_URL', plugin_dir_url( __FILE__ ) );
define( 'AATG_TEXT_GENERATOR_VERSION', '2.1.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ai-alt-text-generator-activator.php
 */
function aatg_activate_text_generator() {
    require_once AATG_TEXT_GENERATOR_PATH . 'includes/class-ai-alt-text-generator-activator.php';
    AATG_Text_Generator_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ai-alt-text-generator-deactivator.php
 */
function aatg_deactivate_text_generator() {
    require_once AATG_TEXT_GENERATOR_PATH . 'includes/class-ai-alt-text-generator-deactivator.php';
    AATG_Text_Generator_Deactivator::deactivate();
}

/**
 * Add Settings link to plugins page
 */
function aatg_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=ai-alt-text-generator#/general') . '">' . __('Settings', 'ai-alt-text-generator') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

register_activation_hook( __FILE__, 'aatg_activate_text_generator' );
register_deactivation_hook( __FILE__, 'aatg_deactivate_text_generator' );
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'aatg_add_settings_link');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require AATG_TEXT_GENERATOR_PATH . 'includes/class-ai-alt-text-generator.php';
require AATG_TEXT_GENERATOR_PATH . 'includes/class-ai-alt-text-generator-restpoint.php';
require AATG_TEXT_GENERATOR_PATH . 'includes/providers/provider-factory.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function aatg_run_text_generator() {
    global $ai_alt_text_generator;
    $ai_alt_text_generator = new AATG_Text_Generator();
    $ai_alt_text_generator->run();
}
aatg_run_text_generator();
