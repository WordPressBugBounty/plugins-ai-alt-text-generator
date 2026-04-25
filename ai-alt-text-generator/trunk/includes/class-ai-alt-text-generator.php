<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 
 * @since      1.0.0
 *
 * @package    AATG_Text_Generator
 * @subpackage AATG_Text_Generator/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    AATG_Text_Generator
 * @subpackage AATG_Text_Generator/includes
 * @author     codersantosh <codersantosh@gmail.com>
 */
class AATG_Text_Generator {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      AATG_Text_Generator_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
        $this->version = AATG_TEXT_GENERATOR_VERSION;
		$this->plugin_name = 'ai-alt-text-generator';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_include_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - AATG_Text_Generator_Loader. Orchestrates the hooks of the plugin.
	 * - AATG_Text_Generator_i18n. Defines internationalization functionality.
	 * - AATG_Text_Generator_Admin. Defines all hooks for the admin area.
	 * - AATG_Text_Generator_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * Plugin Core Functions.
		 */
		require_once AATG_TEXT_GENERATOR_PATH . 'includes/functions.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once AATG_TEXT_GENERATOR_PATH . 'includes/class-ai-alt-text-generator-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once AATG_TEXT_GENERATOR_PATH . 'includes/class-ai-alt-text-generator-i18n.php';

        /**
         * The class responsible for defining all actions that occur in both admin and public-facing areas.
         */
        require_once AATG_TEXT_GENERATOR_PATH . 'includes/class-ai-alt-text-generator-include.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once AATG_TEXT_GENERATOR_PATH . 'admin/class-ai-alt-text-generator-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once AATG_TEXT_GENERATOR_PATH . 'public/class-ai-alt-text-generator-public.php';

		$this->loader = new AATG_Text_Generator_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the AATG_Text_Generator_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new AATG_Text_Generator_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

    /**
     * Register all of the hooks related to both admin and public-facing areas functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_include_hooks() {

        $plugin_admin = new AATG_Text_Generator_Include( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'init', $plugin_admin, 'init_something' );

    }

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new AATG_Text_Generator_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_resources' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_media_admin_scripts' );
        $this->loader->add_action( 'admin_head', $plugin_admin, 'add_admin_css' );
        $this->loader->add_action( 'wp_ajax_generate_alt_text', $plugin_admin, 'generate_alt_text_ajax' );
		$this->loader->add_action('wp_ajax_nopriv_generate_alt_text', $plugin_admin, 'generate_alt_text_ajax');
		$this->loader->add_action('generate_alt_text_for_image', $plugin_admin, 'generate_alt_text_for_image_function');

		if (isset(get_option('aatg_text_generator_options')['on_upload_alt_text']) && get_option('aatg_text_generator_options')['on_upload_alt_text']) {
			$this->loader->add_action('add_attachment', $plugin_admin, 'generate_alt_text_on_upload');
		}

        /*Register Settings*/
        $this->loader->add_action( 'rest_api_init', $plugin_admin, 'register_settings' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

		
		$this->loader->add_filter( 'bulk_actions-upload', $plugin_admin, 'add_bulk_action_option' );
		$this->loader->add_filter( 'handle_bulk_actions-upload', $plugin_admin, 'handle_bulk_action', 10, 3 );

        $this->loader->add_action('admin_notices', $plugin_admin, 'show_bulk_processing_notice');

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new AATG_Text_Generator_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_public_resources' );

    }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    AATG_Text_Generator_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get image data either from post ID or direct URL
	 * 
	 * @param int|null $post_id The attachment post ID
	 * @param string|null $image_url Direct URL to the image
	 * @return array|WP_Error Array with image data or WP_Error on failure
	 */
	private function get_image_data($post_id = null, $image_url = null) {
		// If we have an image URL, try to work with that first
		if ($image_url) {
			// Validate if the URL is from the same domain for security
			$site_url = parse_url(get_site_url(), PHP_URL_HOST);
			$image_host = parse_url($image_url, PHP_URL_HOST);
			
			if ($site_url !== $image_host) {
				return new WP_Error('invalid_url', 'Image URL must be from the same site');
			}

			// Try to find the attachment ID from the URL
			$attachment_id = attachment_url_to_postid($image_url);
			
			// Even if we can't find the attachment ID, we can still work with the URL
			return array(
				'url' => $image_url,
				'post_id' => $attachment_id ?: null,
				'current_alt' => $attachment_id ? get_post_meta($attachment_id, '_wp_attachment_image_alt', true) : ''
			);
		}
		// Fallback to post ID if provided
		elseif ($post_id) {
			$attachment = get_post($post_id);
			if (!$attachment) {
				return new WP_Error('invalid_attachment', 'Invalid attachment ID');
			}

			$image_url = wp_get_attachment_url($post_id);
			if (!$image_url) {
				return new WP_Error('no_url', 'Could not get image URL');
			}

			$alt_text = get_post_meta($post_id, '_wp_attachment_image_alt', true);
			
			return array(
				'url' => $image_url,
				'post_id' => $post_id,
				'current_alt' => $alt_text
			);
		}
		
		return new WP_Error('missing_data', 'Image URL is required');
	}

	/**
	 * Generate alt text via AJAX
	 *
	 * @access public
	 */
	public function generate_alt_text_ajax() {
		// Verify nonce and permissions
		if (!check_ajax_referer('ai_alt_text_generator_nonce', 'nonce', false)) {
			wp_send_json_error('Invalid nonce');
			return;
		}

		if (!current_user_can('upload_files')) {
			wp_send_json_error('Permission denied');
			return;
		}

		// Get image URL from POST data
		$image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : null;

		if (empty($image_url)) {
			wp_send_json_error('No image URL provided');
			return;
		}

		// Get image data
		$image_data = $this->get_image_data(null, $image_url);
		
		if (is_wp_error($image_data)) {
			wp_send_json_error($image_data->get_error_message());
			return;
		}

		// Get the API key from options
		$api_key = get_option('aatg_text_generator_options')['api_key'] ?? '';
		if (empty($api_key)) {
			wp_send_json_error('API key not configured');
			return;
		}

		try {
			// Generate alt text using your AI service
			$generated_alt_text = $this->generate_alt_text_from_image($image_data['url'], $api_key);
			
			if (empty($generated_alt_text)) {
				wp_send_json_error('Failed to generate alt text');
				return;
			}

			// If we have an attachment ID, update its alt text
			if ($image_data['post_id']) {
				update_post_meta($image_data['post_id'], '_wp_attachment_image_alt', $generated_alt_text);
			}

			wp_send_json_success($generated_alt_text);
		} catch (Exception $e) {
			wp_send_json_error('Error generating alt text: ' . $e->getMessage());
		}
	}

	/**
	 * Generate alt text from image using AI service
	 * 
	 * @param string $image_url The URL of the image
	 * @param string $api_key The API key for the AI service
	 * @return string|false The generated alt text or false on failure
	 */
	private function generate_alt_text_from_image($image_url, $api_key) {
		// Make sure we have a valid URL
		if (empty($image_url)) {
			throw new Exception('No image URL provided');
		}

		// Your existing AI service integration code here
		// This is where you would call your AI service (e.g., OpenAI, Google Cloud Vision, etc.)
		// and get back the generated alt text

		// For example, if using OpenAI:
		$response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json',
			),
			'body' => json_encode(array(
				'model' => 'gpt-4-vision-preview',
				'messages' => array(
					array(
						'role' => 'user',
						'content' => array(
							array(
								'type' => 'text',
								'text' => 'Generate a concise, descriptive alt text for this image. Focus on the main subject and important details.'
							),
							array(
								'type' => 'image_url',
								'image_url' => array(
									'url' => $image_url
								)
							)
						)
					)
				),
				'max_tokens' => 100
			))
		));

		if (is_wp_error($response)) {
			throw new Exception('API request failed: ' . $response->get_error_message());
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);
		
		if (empty($body['choices'][0]['message']['content'])) {
			throw new Exception('Invalid API response');
		}

		return trim($body['choices'][0]['message']['content']);
	}

}
