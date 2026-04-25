<?php

class AATG_Text_Generator_Restpoint {
    private $batch_size = 10;
	private $rewrite_all = false;

    public function __construct() {
		$options = get_option('aatg_text_generator_options');
		$this->rewrite_all = is_array($options) && isset($options['all_alt_text']) ? $options['all_alt_text'] : false;
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('ai_process_media_batch', array($this, 'process_media_batch'), 10, 1);
    }

    public function register_rest_routes() {
        register_rest_route('ai-alt-text-generator/v1', '/start-processing', array(
            'methods' => 'POST',
            'callback' => array($this, 'start_processing'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-alt-text-generator/v1', '/process-next', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_next_image'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-alt-text-generator/v1', '/processing-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_processing_status'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-alt-text-generator/v1', '/is-processing', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_processing_status'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-alt-text-generator/v1', '/stop-processing', array(
            'methods' => 'POST',
            'callback' => array($this, 'stop_processing'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-alt-text-generator/v1', '/validate-key', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_api_key'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-alt-text-generator/v1', '/settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'update_settings'),
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ),
        ));

        register_rest_route('ai-alt-text-generator/v1', '/generate-test', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_test_generation'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));
    }

    public function start_processing(WP_REST_Request $request) {
        try {
            // Clear any previous transient
            delete_transient('aatg_bulk_generation_ids');

            // Get total number of images to process
            $args = array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => 'image',
                'posts_per_page' => -1,
                'fields'         => 'ids'
            );

            // If not processing all images, only get those without alt text
            if (!$this->rewrite_all) {
                $ids = $this->get_images_without_alt_text_ids();
                if (!empty($ids)) {
                    $args['post__in'] = $ids;
                }
            }

            $total_images = count(get_posts($args));

            if ($total_images === 0) {
                return new WP_REST_Response(array(
                    'status' => 'error',
                    'message' => 'No images found to process'
                ), 200);
            }

            // Store processing state
            update_option('aatg_is_processing', true);
            update_option('aatg_processing_total', $total_images);
            update_option('aatg_processing_current', 0);

            return new WP_REST_Response(array(
                'status' => 'success',
                'message' => sprintf('Found %d images to process', $total_images),
                'total_items' => $total_images,
                'is_processing' => true
            ), 200);

        } catch (Exception $e) {
            // Clean up on error
            update_option('aatg_is_processing', false);
            update_option('aatg_processing_total', 0);
            update_option('aatg_processing_current', 0);
            
            return new WP_REST_Response(array(
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ), 500);
        }
    }

    public function process_next_image() {
        try {
            if (!get_option('aatg_is_processing', false)) {
                return new WP_REST_Response(array(
                    'status' => 'error',
                    'message' => 'Processing is not active'
                ), 200);
            }

            $current = get_option('aatg_processing_current', 0);
            $total = get_option('aatg_processing_total', 0);

            if ($current >= $total) {
                update_option('aatg_is_processing', false);
                update_option('aatg_processing_total', 0);
                update_option('aatg_processing_current', 0);
                return new WP_REST_Response(array(
                    'status' => 'completed',
                    'message' => 'All images processed',
                    'current' => $current,
                    'total' => $total
                ), 200);
            }

            $args = array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => 'image',
                'posts_per_page' => 1,
                'offset'         => $current
            );

            // Check if we're processing a specific list of IDs from a bulk action
            $bulk_ids = get_transient('aatg_bulk_generation_ids');

            if ($bulk_ids && is_array($bulk_ids)) {
                $args['post__in'] = $bulk_ids;
                $args['orderby'] = 'post__in';
            } elseif (!$this->rewrite_all) {
                $ids = $this->get_images_without_alt_text_ids();
                if (empty($ids)) {
                    update_option('aatg_is_processing', false);
                    update_option('aatg_processing_total', 0);
                    update_option('aatg_processing_current', 0);
                    // Clear transient if it exists
                    delete_transient('aatg_bulk_generation_ids');
                    return new WP_REST_Response(array(
                        'status' => 'completed',
                        'message' => 'No more images to process',
                        'current' => $current,
                        'total' => $total
                    ), 200);
                }
                $args['post__in'] = $ids;
            }

            $media_items = get_posts($args);
            
            if (empty($media_items)) {
                update_option('aatg_is_processing', false);
                update_option('aatg_processing_total', 0);
                update_option('aatg_processing_current', 0);
                // Clear transient if it exists
                delete_transient('aatg_bulk_generation_ids');
                return new WP_REST_Response(array(
                    'status' => 'completed',
                    'message' => 'No more images to process',
                    'current' => $current,
                    'total' => $total
                ), 200);
            }

            $item = $media_items[0];
            
            // Get provider and API key from options
            $options = get_option('aatg_text_generator_options', array());
            $provider = $options['ai_provider'] ?: 'openai';
            $api_key_field = $provider . '_key';
            
            if (empty($options[$api_key_field])) {
                throw new Exception('API key for ' . $provider . ' is not configured');
            }
            
            $api_key = $options[$api_key_field];

            // Get image file path
            $upload_dir = wp_upload_dir();
            $image_meta = wp_get_attachment_metadata($item->ID);
            
            if (!$image_meta || !isset($image_meta['file'])) {
                throw new Exception('Failed to get image metadata');
            }

            // Get the full server path to the image
            $image_path = $upload_dir['basedir'] . '/' . $image_meta['file'];

            // Check if file exists
            if (!file_exists($image_path)) {
                throw new Exception('Image file not found');
            }

            // Read image file directly
            $image_data = file_get_contents($image_path);
            if ($image_data === false) {
                throw new Exception('Failed to read image file');
            }

            // Convert image to base64
            $image_base64 = base64_encode($image_data);
            if (empty($image_base64)) {
                throw new Exception('Failed to process image');
            }

            // Generate alt text using the selected provider
            $prompt = $options['prompt'] ?: 'Create a SEO optimized alt text for this image. Don\'t include quotes and keep it informative and concise.';
            $language = $options['language'] ?: 'english';
            
            $result = AATG_Provider_Factory::generate_alt_text(
                $provider,
                $image_base64,
                $prompt,
                $language,
                $api_key
            );
            
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            
            $alt_text = $result['alt_text'];

            // Update the alt text
            update_post_meta($item->ID, '_wp_attachment_image_alt', $alt_text);
            $current++;
            update_option('aatg_processing_current', $current);

            // If processing is complete, clear the transient
            if ($current >= $total) {
                delete_transient('aatg_bulk_generation_ids');
            }

            return new WP_REST_Response(array(
                'status' => 'success',
                'message' => 'Image processed successfully',
                'current' => $current,
                'total' => $total,
                'is_processing' => true
            ), 200);

        } catch (Exception $e) {
            // Skip this image but continue processing
            $current++;
            update_option('aatg_processing_current', $current);
            
            return new WP_REST_Response(array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'current' => $current,
                'total' => $total,
                'is_processing' => true
            ), 200);
        }
    }

    public function validate_api_key(WP_REST_Request $request) {
        $key = $request->get_param('key');
        $provider = $request->get_param('provider');
        
        if (empty($key)) {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => 'API key is required'
            ), 400);
        }
        
        if (empty($provider)) {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => 'Provider is required'
            ), 400);
        }

        $result = AATG_Provider_Factory::validate_api_key($provider, $key);
        
        $status_code = $result['valid'] ? 200 : 400;
        return new WP_REST_Response($result, $status_code);
    }



	public function process_media_batch($batch_size) {
        if (!get_option('aatg_is_processing', false)) {
            update_option('aatg_processing_total', 0);
            update_option('aatg_processing_current', 0);
            return;
        }

        $args = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $batch_size,
            'offset'         => get_option('aatg_processing_current', 0)
        );

        // If not rewriting all, fetch images without alt text
        if (!$this->rewrite_all) {
            $ids = $this->get_images_without_alt_text_ids();
            if (empty($ids)) {
                update_option('aatg_is_processing', false);
                update_option('aatg_processing_total', 0);
                update_option('aatg_processing_current', 0);
                return;
            }
            $args['post__in'] = $ids;
        }
    
        $media_items = get_posts($args);
    
        if (empty($media_items)) {
            update_option('aatg_is_processing', false);
            update_option('aatg_processing_total', 0);
            update_option('aatg_processing_current', 0);
            return;
        }

        $admin_instance = AATG_Text_Generator_Admin::get_instance();
        $current = get_option('aatg_processing_current', 0);
        $total = get_option('aatg_processing_total', 0);

        foreach ($media_items as $item) {
            if (!get_option('aatg_is_processing', false)) {
                return;
            }

            $image_url = $admin_instance->get_image_url_by_size($item->ID, 'thumbnail');
            
            if (!$image_url) {
                continue;
            }

            try {
                $alt_text = $admin_instance->generate_alt_text_with_ai($image_url);
        
                if ($alt_text) {
                    update_post_meta($item->ID, '_wp_attachment_image_alt', $alt_text);
                    $current++;
                    update_option('aatg_processing_current', $current);

                    // Check if we've processed all images
                    if ($current >= $total) {
                        update_option('aatg_is_processing', false);
                        update_option('aatg_processing_total', 0);
                        update_option('aatg_processing_current', 0);
                        delete_transient('aatg_bulk_generation_ids');
                        return;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }
    
        if (get_option('aatg_is_processing', false)) {
            wp_schedule_single_event(time() + 5, 'ai_process_media_batch', array($batch_size));
        }
	}

	private function get_images_without_alt_text_ids() {
		global $wpdb;

		$query = "
			SELECT p.ID
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
			WHERE p.post_type = 'attachment'
			AND p.post_mime_type LIKE 'image%'
			AND (pm.meta_value IS NULL OR pm.meta_value = '')
		";

		$results = $wpdb->get_results($query);
		$ids = array_map(function($result) {
			return $result->ID;
		}, $results);

		return $ids;
	}

    public function get_settings() {
        $defaults = aatg_text_generator_default_options();
        $options = get_option('aatg_text_generator_options', $defaults);
        
        // Add provider options and their default models for the frontend
        $providers = AATG_Provider_Factory::get_providers();
        $provider_options = array();
        $default_models = array();

        foreach ($providers as $name => $provider) {
            $provider_options[$name] = $provider->get_display_name();
            $default_models[$name] = $provider->get_default_model();
        }

        $options['available_providers'] = $provider_options;
        $options['default_models'] = $default_models;

        // Ensure a model is set, if not, use the default for the current provider
        if (empty($options['model'])) {
            $current_provider = $options['ai_provider'] ?? 'openai';
            $options['model'] = $default_models[$current_provider] ?? '';
        }
        
        return new WP_REST_Response($options, 200);
    }

    public function update_settings(WP_REST_Request $request) {
        $settings = $request->get_params();
        
        $defaults = aatg_text_generator_default_options();
        
        // Remove available_providers from settings if it exists (it's read-only)
        unset($settings['available_providers']);
        
        // Ensure we have all required fields
        $settings = wp_parse_args($settings, $defaults);
        
        // Delete the option first to ensure it's updated
        delete_option('aatg_text_generator_options');
        
        // Save the new settings
        $result = update_option('aatg_text_generator_options', $settings, false);
        
        // Verify the save
        $saved = get_option('aatg_text_generator_options');
        
        if (!$result || !$saved) {
            return new WP_REST_Response(array(
                'error' => 'Failed to save settings',
                'settings' => $settings
            ), 500);
        }
        
        // Re-add provider options for frontend (since it's read-only)
        $saved['available_providers'] = AATG_Provider_Factory::get_provider_options();
        
        return new WP_REST_Response($saved, 200);
    }

    public function check_processing_status() {
        $is_processing = get_option('aatg_is_processing', false);
        return new WP_REST_Response(array('is_processing' => $is_processing), 200);
    }

    public function stop_processing() {
        update_option('aatg_is_processing', false);
        update_option('aatg_processing_total', 0);
        update_option('aatg_processing_current', 0);
        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => 'Processing stopped'
        ), 200);
    }

    public function get_processing_status() {
        $is_processing = get_option('aatg_is_processing', false);
        $total_items = get_option('aatg_processing_total', 0);
        $current_item = get_option('aatg_processing_current', 0);

        // Validate the status - if current equals total, processing is done
        if ($total_items > 0 && $current_item >= $total_items) {
            update_option('aatg_is_processing', false);
            update_option('aatg_processing_total', 0);
            update_option('aatg_processing_current', 0);
            $is_processing = false;
            $total_items = 0;
            $current_item = 0;
        }

        // If not processing, ensure counters are reset
        if (!$is_processing) {
            update_option('aatg_processing_total', 0);
            update_option('aatg_processing_current', 0);
            $total_items = 0;
            $current_item = 0;
        }

        return new WP_REST_Response(array(
            'is_processing' => $is_processing,
            'total_items' => $total_items,
            'current_item' => $current_item
        ), 200);
    }

    public function handle_test_generation(WP_REST_Request $request) {
        try {
            $image_id = $request->get_param('image_id');
            $custom_prompt = $request->get_param('prompt');

            if (!$image_id) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Image ID is required'
                ), 400);
            }

            // Get provider and API key from options
            $options = get_option('aatg_text_generator_options', array());
            $provider = $options['ai_provider'] ?: 'openai';
            $api_key_field = $provider . '_key';
            
            if (empty($options[$api_key_field])) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'API key for ' . $provider . ' is not configured'
                ), 400);
            }
            
            $api_key = $options[$api_key_field];

            // Get image file path instead of URL
            $upload_dir = wp_upload_dir();
            $image_meta = wp_get_attachment_metadata($image_id);
            
            if (!$image_meta || !isset($image_meta['file'])) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Failed to get image metadata'
                ), 400);
            }

            // Get the full server path to the image
            $image_path = $upload_dir['basedir'] . '/' . $image_meta['file'];

            // Check if file exists
            if (!file_exists($image_path)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Image file not found'
                ), 400);
            }

            // Read image file directly
            $image_data = file_get_contents($image_path);
            if ($image_data === false) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Failed to read image file'
                ), 400);
            }

            // Convert image to base64
            $image_base64 = base64_encode($image_data);
            if (empty($image_base64)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Failed to process image'
                ), 400);
            }

            // Generate alt text using the selected provider
            $prompt = $custom_prompt ?: $options['prompt'] ?: 'Create a SEO optimized alt text for this image. Don\'t include quotes and keep it informative and concise.';
            $language = $options['language'] ?: 'english';
            
            $result = AATG_Provider_Factory::generate_alt_text(
                $provider,
                $image_base64,
                $prompt,
                $language,
                $api_key
            );
            
            if (!$result['success']) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => $result['message']
                ), 400);
            }
            
            $alt_text = $result['alt_text'];

            return new WP_REST_Response(array(
                'success' => true,
                'alt_text' => $alt_text
            ), 200);

        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ), 500);
        }
    }
}

// Initialize the class
new AATG_Text_Generator_Restpoint();
