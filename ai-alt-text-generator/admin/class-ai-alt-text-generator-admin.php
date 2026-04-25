<?php

/**
 * The admin-specific functionality of the plugin.
 *
 
 * @since      1.0.0
 *
 * @package    AATG_Text_Generator
 * @subpackage AATG_Text_Generator/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    AATG_Text_Generator
 * @subpackage AATG_Text_Generator/admin
 * @author     codersantosh <codersantosh@gmail.com>
 */
class AATG_Text_Generator_Admin {

    private static $instance = null;

	/**
	 * The ID of this plugin.
     * Used on slug of plugin menu.
     * Used on Root Div ID for React too.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action('init', array($this, 'register_ajax_handlers'));

	}

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self( 'ai-alt-text-generator', '1.0.0', '1.0.0');
        }
        return self::$instance;
    }

    /**
     * Add Admin Page Menu page.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {

        add_menu_page(
            esc_html__( 'AI Alt Generator', 'ai-alt-text-generator' ),
            esc_html__( 'AI Alt Generator', 'ai-alt-text-generator' ),
            'manage_options',
            $this->plugin_name,
            array( $this, 'add_setting_root_div' ),
            plugin_dir_url( __FILE__ ) . 'images/alt-icon.png' // Add the icon path here
        );
    }

    /**
     * Add Root Div For React.
     *
     * @since    1.0.0
     */
    public function add_setting_root_div() {
        echo '<div id="' . esc_attr( $this->plugin_name ) . '"></div>';
    }

	/**
	 * Register the CSS/JavaScript Resources for the admin area.
	 *
	 * Use Condition to Load it Only When it is Necessary
	 *
	 * @since    1.0.0
	 */
	public function enqueue_resources() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in AATG_Text_Generator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The AATG_Text_Generator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$screen              = get_current_screen();
		$admin_scripts_bases = array( 'toplevel_page_' . $this->plugin_name );
		if ( ! ( isset( $screen->base ) && in_array( $screen->base, $admin_scripts_bases ) ) ) {
			return;
		}

        // Enqueue WordPress media scripts
        wp_enqueue_media();

        wp_enqueue_style( 'at-grid', esc_url( AATG_TEXT_GENERATOR_URL . 'assets/library/at-grid/at-grid.min.css'), array(), $this->version );

        $at_grid_css_var = "
            :root{
                --at-container-sm: 540px;
                --at-container-md: 720px;
                --at-container-lg: 960px;
                --at-container-xl: 1140px;
                --at-gutter:15px;
            }
        ";
        wp_add_inline_style( 'at-grid', $at_grid_css_var );

        /*Scripts dependency files*/
        $deps_file = AATG_TEXT_GENERATOR_PATH . 'build/admin/settings.asset.php';

        /*Fallback dependency array*/
        $dependency = [];
        $version = $this->version;

        /*Set dependency and version*/
        if ( file_exists( $deps_file ) ) {
            $deps_file = require( $deps_file );
            $dependency      = $deps_file['dependencies'];
            $version      = $deps_file['version'];
        }


		$version = filemtime( AATG_TEXT_GENERATOR_PATH . 'build/admin/settings.js' );
		wp_enqueue_script( $this->plugin_name, esc_url( AATG_TEXT_GENERATOR_URL . 'build/admin/settings.js' ), $dependency, $version, true );

		$style_version = filemtime( AATG_TEXT_GENERATOR_PATH . 'build/admin/style-settings.css' );
		wp_enqueue_style( $this->plugin_name, esc_url( AATG_TEXT_GENERATOR_URL . 'build/admin/style-settings.css' ), array('wp-components'), $style_version );

		$localize = array(
			'version' => $this->version,
			'root_id' => $this->plugin_name,
		);
        wp_set_script_translations( $this->plugin_name, $this->plugin_name );
		wp_localize_script( $this->plugin_name, 'wpReactPluginBoilerplateBuild', $localize );
	}

    public function add_bulk_action_option( $bulk_actions ) {
        $bulk_actions['generate_alt_text'] = esc_html__( 'Generate Alt Text', 'ai-alt-text-generator' );
        return $bulk_actions;
    }

    public function enqueue_media_admin_scripts() {
        // only in media and post edit pages
        if ( ! ( 'post.php' === $GLOBALS['pagenow'] || 'post-new.php' === $GLOBALS['pagenow'] || 'upload.php' === $GLOBALS['pagenow'] ) ) {
            return;
        }
        wp_enqueue_script( 'ai-alt-text-generator-media', esc_url( plugin_dir_url( __FILE__ ) . 'js/media-button.js' ), array( 'jquery' ), time(), true );
        wp_enqueue_script(
            'alt-gen-gutenberg-blocks',
            AATG_TEXT_GENERATOR_URL . 'build/admin/blocks.js', // Adjust the path
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'),
            $this->version
        );
        $nonce = wp_create_nonce( 'alt_gen_ajax_nonce' );
        wp_localize_script( 'ai-alt-text-generator-media', 'aiAltTextGenerator', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => $nonce ) );
    }

    public function show_bulk_processing_notice() {
        if (get_option('aatg_is_processing')) {
            $total = get_option('aatg_processing_total', 0);
            $current = get_option('aatg_processing_current', 0);
            $progress = $total > 0 ? round(($current / $total) * 100) : 0;
            
            ?>
            <div class="notice notice-info is-dismissible aatg-progress-notice">
                <p>
                    <strong><?php _e('AI Alt Text Generation in Progress', 'ai-alt-text-generator'); ?></strong>
                </p>
                <div style="width: 100%; background-color: #e0e0e0; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
                    <div style="width: <?php echo $progress; ?>%; background-color: #0073aa; color: white; text-align: center; line-height: 20px;">
                        <?php echo $progress; ?>%
                    </div>
                </div>
                <p>
                    <?php printf(__('Processed %d of %d images.', 'ai-alt-text-generator'), $current, $total); ?>
                    <button id="aatg-manual-trigger" class="button" style="margin-left: 10px;"><?php _e('Process Next Batch', 'ai-alt-text-generator'); ?></button>
                    <span class="spinner" style="float: none; margin-left: 5px;"></span>
                </p>
            </div>
            <script>
                jQuery(document).ready(function($) {
                    var notice = $('.aatg-progress-notice');
                    var triggerButton = $('#aatg-manual-trigger');
                    var spinner = notice.find('.spinner');

                    function updateProgress(response) {
                        if (response.is_processing) {
                            var progress = response.total_items > 0 ? Math.round((response.current_item / response.total_items) * 100) : 0;
                            notice.find('.notice-info div > div').css('width', progress + '%').text(progress + '%');
                            notice.find('p:first-of-type + p').html('Processed ' + response.current_item + ' of ' + response.total_items + ' images. <button id="aatg-manual-trigger" class="button" style="margin-left: 10px;">Process Next Batch</button>');
                        } else {
                            notice.removeClass('notice-info').addClass('notice-success').html('<p>Bulk generation complete!</p>');
                            clearInterval(interval);
                            setTimeout(function() {
                                notice.fadeOut();
                            }, 5000);
                        }
                    }

                    var interval = setInterval(function() {
                        $.get(ajaxurl, { action: 'aatg_processing_status' }, updateProgress);
                    }, 10000);

                    $(document).on('click', '#aatg-manual-trigger', function() {
                        spinner.addClass('is-active');
                        triggerButton.prop('disabled', true);
                        
                        $.post(ajaxurl, { action: 'aatg_process_next_batch' }, function(response) {
                            updateProgress(response);
                            spinner.removeClass('is-active');
                            triggerButton.prop('disabled', false);
                        });
                    });
                });
            </script>
            <?php
        }
    }

    public function add_admin_css() {
        echo '<style>
        #toplevel_page_' . esc_attr( $this->plugin_name ) . ' a.menu-top img {
            opacity: 1;
            width: 28px;
            margin-top: -5px;
        }
        </style>';
    }
    

    // AJAX Handler Function
    public function generate_alt_text_ajax() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'alt_gen_ajax_nonce')) {
                wp_send_json_error('Security check failed.');
                return;
            }

            $attachment_id = null;

            if (!empty($_POST['post_id'])) {
                $attachment_id = absint($_POST['post_id']);
            }

            if (!$attachment_id) {
                wp_send_json_error('Could not find a valid image ID in the request.');
                return;
            }

            // Get the image URL from the attachment ID
            $image_url = wp_get_attachment_url($attachment_id);
            
            if (!$image_url) {
                wp_send_json_error('Could not find an image URL for ID: ' . $attachment_id);
                return;
            }

            // Generate alt text using AI provider
            $alt_text = $this->generate_alt_text_with_ai($image_url);
            if (empty($alt_text)) {
                wp_send_json_error('The AI provider failed to generate alt text.');
                return;
            }

            // Update the alt text
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);

            wp_send_json_success($alt_text);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    

    public function handle_bulk_action($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'generate_alt_text') {
            
            if (empty($post_ids) || !is_array($post_ids)) {
                return add_query_arg('aatg_error', 'no_images_selected', $redirect_to);
            }

            // Immediately process the first image to provide instant feedback
            if (!empty($post_ids)) {
                $first_image_id = array_shift($post_ids);
                $this->generate_alt_text_for_image_function($first_image_id);
                // Update the count to reflect one image processed
                update_option('aatg_processing_current', 1);
            }
            
            // If there are more images, store them in the transient for the background processor
            if (!empty($post_ids)) {
                set_transient('aatg_bulk_generation_ids', $post_ids, HOUR_IN_SECONDS);
                wp_schedule_single_event(time() + 5, 'ai_process_media_batch', array(10));
            } else {
                // If only one image was processed, we're done
                update_option('aatg_is_processing', false);
            }

            // Add a query arg to notify the user
            $redirect_to = add_query_arg('aatg_message', 'bulk_started', $redirect_to);
        }
        return $redirect_to;
    }

    public function get_image_url_by_size($post_id, $size = 'thumbnail') {
        // Get the image attachment URL in the specified size
        $image = wp_get_attachment_image_src($post_id, $size);
    
        // Check if the image exists
        if ($image) {
            return $image[0]; // URL of the image
        }
    
        return false; // Return false if the image doesn't exist
    }

    public function generate_alt_text_for_image_function( $post_id ) {
        // Get the smaller image URL (e.g., thumbnail)
        $image_url = $this->get_image_url_by_size($post_id, 'thumbnail');

        // Generate alt text using the AI provider
        $alt_text = $this->generate_alt_text_with_ai( $image_url );

        // Update the alt text for the media item
        update_post_meta( $post_id, '_wp_attachment_image_alt', $alt_text );
    }

    public function generate_alt_text_on_upload( $attachment_id ) {
        // Check if the attachment is an image
        if ( wp_attachment_is_image( $attachment_id ) ) {
            // Get the image URL
            $image_url = $this->get_image_url_by_size($attachment_id, 'thumbnail');

            // Generate alt text using AI provider
            $alt_text = $this->generate_alt_text_with_ai( $image_url );

            // Update the alt text for the media item
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
        }
    }

    public function generate_alt_text_with_ai($image_url) {
        try {
            // Get settings
            $options = get_option('aatg_text_generator_options');
            $provider = $options['ai_provider'] ?? 'openai';
            $api_key_field = $provider . '_key';
            
            if (empty($options[$api_key_field])) {
                return '';
            }

            // Get image data using WordPress functions
            $upload_dir = wp_upload_dir();
            $image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image_url);
            
            if (!file_exists($image_path)) {
                return '';
            }

            $image_data = file_get_contents($image_path);
            if ($image_data === false) {
                return '';
            }

            $image_base64 = base64_encode($image_data);

            // Get prompt and language from settings
            $prompt = $options['prompt'] ?? 'Create a SEO optimized alt text for this image. Don\'t include quotes and keep it informative and concise.';
            $language = $options['language'] ?? 'english';

            // Use the provider factory to generate alt text
            $result = AATG_Provider_Factory::generate_alt_text(
                $provider,
                $image_base64,
                $prompt,
                $language,
                $options[$api_key_field]
            );
            
            if (!$result['success']) {
                return '';
            }

            $alt_text = $result['alt_text'];
            return $alt_text;

        } catch (Exception $e) {
            return '';
        }
    }
    
    // Keep the old method name for backward compatibility
    public function generate_alt_text_with_openai($image_url) {
        return $this->generate_alt_text_with_ai($image_url);
    }

    private function generate_alt_text_with_openai_batch( $images_data ) {
        // OpenAI API key from settings
        $openai_key = get_option( 'aatg_text_generator_options' )['openai_key'];
    
        // Prepare tasks
        $tasks = [];
        foreach ( $images_data as $index => $image_data ) {
            $sanitized_url = esc_url_raw( $image_data['image_url'] );
            $image = file_get_contents( $sanitized_url );
            if ( $image === false ) {
                // Handle error, perhaps log it or notify the admin
                continue;
            }
            $image_base64 = base64_encode( $image );
    
            $task = [
                'custom_id' => 'task-' . $index,
                'method' => 'POST',
                'url' => '/v1/chat/completions',
                'body' => [
                    'model' => 'gpt-4o',
                    'temperature' => 0.2,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Your goal is to generate a SEO optimized alt text for this image. Don\'t include quotes and keep it informative and concise.',
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Create a SEO optimized alt text for this image. Don\'t include quotes and keep it informative and concise.',
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:image/jpeg;base64,' . $image_base64,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
    
            $tasks[] = $task;
        }
    
        // Create JSONL file
        $file_name = 'batch_tasks_images.jsonl';
        $file_path = wp_upload_dir()['basedir'] . '/' . $file_name;
        $file = fopen( $file_path, 'w' );
        foreach ( $tasks as $task ) {
            fwrite( $file, json_encode( $task ) . "\n" );
        }
        fclose( $file );
    
        // Upload the JSONL file to OpenAI using curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/files");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $openai_key,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file' => new CURLFile($file_path, 'application/jsonl', $file_name),
            'purpose' => 'batch',
        ]);
    
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            // Handle error
            return [];
        }
        curl_close($ch);
    
        $file_data = json_decode($response, true);
        $file_id = $file_data['id'] ?? '';
    
        if ( empty( $file_id ) ) {
            // Handle error
            return [];
        }
    
        // Create the batch job
        $batch_url = 'https://api.openai.com/v1/batches';
        $batch_response = wp_remote_post( $batch_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $openai_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'input_file_id' => $file_id,
                'endpoint' => '/v1/chat/completions',
                'completion_window' => '24h',
            ]),
        ]);
    
        if ( is_wp_error( $batch_response ) ) {
            // Handle error
            return [];
        }
    
        $batch_body = wp_remote_retrieve_body( $batch_response );
        $batch_data = json_decode( $batch_body, true );
        $batch_id = $batch_data['id'] ?? '';
    
        if ( empty( $batch_id ) ) {
            // Handle error
            return [];
        }
    
        // Check batch status (implement a mechanism to check status periodically if needed)
        $status_url = 'https://api.openai.com/v1/batches/' . $batch_id;
        do {
            $status_response = wp_remote_get( $status_url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $openai_key,
                ],
            ]);
    
            if ( is_wp_error( $status_response ) ) {
                // Handle error
                return [];
            }
    
            $status_body = wp_remote_retrieve_body( $status_response );
            $status_data = json_decode( $status_body, true );
    
            if ( $status_data['status'] === 'completed' ) {
                break;
            }
    
            // Wait before checking again
            sleep(60);
        } while ( $status_data['status'] !== 'completed' );
    
        // Retrieve results
        $result_file_id = $status_data['output_file_id'] ?? '';
        if ( empty( $result_file_id ) ) {
            // Handle error
            return [];
        }
    
        $result_url = 'https://api.openai.com/v1/files/' . $result_file_id . '/content';
        $result_response = wp_remote_get( $result_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $openai_key,
            ],
        ]);
    
        if ( is_wp_error( $result_response ) ) {
            // Handle error
            return [];
        }
    
        $result_body = wp_remote_retrieve_body( $result_response );
        $results = explode("\n", trim( $result_body ));
    
        // Process results
        $alt_texts = [];
        foreach ( $results as $result ) {
            $result_data = json_decode( $result, true );
            $task_id = $result_data['custom_id'] ?? '';
            $alt_text = $result_data['response']['body']['choices'][0]['message']['content'] ?? '';
    
            if ( !empty( $task_id ) && !empty( $alt_text ) ) {
                $index = (int) str_replace('task-', '', $task_id);
                $alt_texts[] = [
                    'post_id' => $images_data[$index]['post_id'],
                    'alt_text' => $alt_text,
                ];
            }
        }
    
        return $alt_texts;
    }
    
    
    
    

    /**
     * Register settings.
     * Common callback function of rest_api_init and admin_init
     * Schema: http://json-schema.org/draft-04/schema#
     *
     * Add your own settings fields here
     *
     * @since 1.0.0
     *
     * @param null.
     * @return void
     */
    public function register_settings() {
        $defaults = aatg_text_generator_default_options();
        register_setting(
            'aatg_text_generator_settings_group',
            'aatg_text_generator_options',
            array(
                'type'         => 'object',
                'default'      => $defaults,
                'show_in_rest' => array(
                    'schema' => array(
                        'type'       => 'object',
                        'properties' => array(
                            /*===Settings===*/
                            /*Settings -> General*/
                            'ai_provider' => array(
                                'type' => 'string',
                                'default' => $defaults['ai_provider'],
                                'sanitize_callback' => 'sanitize_text_field',
                            ),
                            'openai_key' => array(
                                'type'              => 'string',
                                'default'           => $defaults['openai_key'],
                                'sanitize_callback' => 'sanitize_text_field', // Sanitize the API key
                            ),
                            'anthropic_key' => array(
                                'type'              => 'string',
                                'default'           => $defaults['anthropic_key'],
                                'sanitize_callback' => 'sanitize_text_field', // Sanitize the API key
                            ),
                            'on_upload_alt_text' => array(
                                'type' => 'boolean',
                                'default' => $defaults['on_upload_alt_text']
                            ),
                            'all_alt_text' => array(
                                'type' => 'boolean',
                                'default' => $defaults['all_alt_text']
                            ),
                            'prompt' => array(
                                'type' => 'string',
                                'default' => $defaults['prompt']
                            ),
                            'language' => array(
                                'type' => 'string',
                                'default' => $defaults['language']
                            ),
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Register the AJAX handlers
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_generate_alt_text', array($this, 'generate_alt_text_ajax_handler'));
        add_action('wp_ajax_nopriv_generate_alt_text', array($this, 'generate_alt_text_ajax_handler'));
        add_action('wp_ajax_aatg_processing_status', array($this, 'get_processing_status_ajax'));
        add_action('wp_ajax_aatg_process_next_batch', array($this, 'process_next_batch_ajax'));
    }

    /**
     * Handle the generate alt text AJAX request
     */
    public function generate_alt_text_ajax_handler() {
        // Get the main plugin instance
        global $ai_alt_text_generator;
        
        if (method_exists($ai_alt_text_generator, 'generate_alt_text_ajax')) {
            $ai_alt_text_generator->generate_alt_text_ajax();
        } else {
            wp_send_json_error('Method not found');
        }
        
        wp_die();
    }

    public function get_processing_status_ajax() {
        $status = [
            'is_processing' => get_option('aatg_is_processing', false),
            'total_items' => get_option('aatg_processing_total', 0),
            'current_item' => get_option('aatg_processing_current', 0),
        ];
        wp_send_json($status);
    }

    public function process_next_batch_ajax() {
        // Manually run the next batch from the REST point logic
        $rest_point = new AATG_Text_Generator_Restpoint();
        $rest_point->process_media_batch(10); // Process a batch of 10
        
        // Return the latest status
        $this->get_processing_status_ajax();
    }
}
