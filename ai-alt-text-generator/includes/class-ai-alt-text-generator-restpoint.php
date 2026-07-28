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

        // Managed-credit mode (no API key): connect / status / disconnect.
        register_rest_route('ai-alt-text-generator/v1', '/managed/connect', array(
            'methods' => 'POST',
            'callback' => array($this, 'managed_connect'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));
        register_rest_route('ai-alt-text-generator/v1', '/managed/status', array(
            'methods' => 'POST',
            'callback' => array($this, 'managed_status'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));
        register_rest_route('ai-alt-text-generator/v1', '/managed/disconnect', array(
            'methods' => 'POST',
            'callback' => array($this, 'managed_disconnect'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));
    }

    /**
     * Connect (or re-connect) a managed-credit account for this site.
     *
     * Two ways in:
     *  - Paste an existing API token (from the store dashboard — how paid buyers
     *    connect). We validate it against the store and store it as-is.
     *  - Otherwise auto-register a fresh free account (instant trial credits, no
     *    email required). Email is optional and only used to link a later purchase.
     */
    public function managed_connect(WP_REST_Request $request) {
        $token = sanitize_text_field((string) $request->get_param('token'));

        // Path 1: connect with a pasted token.
        if ('' !== $token) {
            $res = aatg_managed_status($token);
            if (is_wp_error($res)) {
                // Business outcome (not a transport error): return 200 so the admin
                // JS reads the structured fields instead of a thrown apiFetch error.
                return new WP_REST_Response(array(
                    'ok'      => false,
                    'error'   => 'invalid_token',
                    'message' => __('That token was not recognized. Copy it again from your dashboard.', 'ai-alt-text-generator'),
                ), 200);
            }
            $options = aatg_text_generator_get_options();
            $options['managed_token']  = $token;
            $options['managed_mode']   = true;
            $options['managed_optout'] = false;
            update_option('aatg_text_generator_options', $options);
            return new WP_REST_Response(array(
                'ok'                => true,
                'status'            => $res['status'] ?? 'active',
                'plan'              => $res['plan'] ?? 'free',
                'credits_remaining' => $res['credits_remaining'] ?? 0,
                'monthly_credits'   => $res['monthly_credits'] ?? 0,
            ), 200);
        }

        // Path 2: auto-register a free account (email optional).
        $email = sanitize_email((string) $request->get_param('email'));
        $res   = aatg_managed_register($email, home_url());
        if (is_wp_error($res)) {
            // A paid plan already exists for this site: send the user to the
            // dashboard to copy their token instead of minting a new one.
            if ('paid_account_exists' === $res->get_error_message()) {
                // Business outcome: 200 so the admin JS can show the dashboard link.
                return new WP_REST_Response(array(
                    'ok'            => false,
                    'error'         => 'paid_account_exists',
                    'message'       => __('A paid plan already exists for this site. Sign in to your dashboard, copy your API token, and paste it here.', 'ai-alt-text-generator'),
                    'dashboard_url' => aatg_store_url() . '/login',
                ), 200);
            }
            return new WP_REST_Response(array('ok' => false, 'error' => $res->get_error_message()), 502);
        }
        $options = aatg_text_generator_get_options();
        if ('' !== $email) {
            $options['managed_email'] = $email;
        }
        $options['managed_token']  = $res['token'];
        $options['managed_ref']    = isset($res['account_ref']) ? $res['account_ref'] : '';
        $options['managed_mode']   = true;
        $options['managed_optout'] = false;
        update_option('aatg_text_generator_options', $options);

        return new WP_REST_Response(array(
            'ok'                => true,
            'status'            => $res['status'] ?? 'active',
            'plan'              => $res['plan'] ?? 'free',
            'credits_remaining' => $res['credits_remaining'] ?? 0,
            'monthly_credits'   => $res['monthly_credits'] ?? 0,
            'email'             => $email,
        ), 200);
    }

    /** Report the connected account's plan + balance. */
    public function managed_status(WP_REST_Request $request) {
        $options = aatg_text_generator_get_options();
        if (empty($options['managed_token'])) {
            return new WP_REST_Response(array('ok' => false, 'error' => 'not_connected'), 400);
        }
        $res = aatg_managed_status($options['managed_token']);
        if (is_wp_error($res)) {
            return new WP_REST_Response(array('ok' => false, 'error' => $res->get_error_message()), 502);
        }
        $res['email'] = $options['managed_email'] ?? '';
        return new WP_REST_Response($res, 200);
    }

    /** Disconnect: clear the token and disable managed mode. */
    public function managed_disconnect(WP_REST_Request $request) {
        $options = aatg_text_generator_get_options();
        $options['managed_mode']  = false;
        $options['managed_token'] = '';
        $options['managed_email'] = '';
        // Remember the choice so auto-connect doesn't immediately reconnect.
        $options['managed_optout'] = true;
        update_option('aatg_text_generator_options', $options);
        return new WP_REST_Response(array('ok' => true), 200);
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
            update_option('aatg_processing_skipped', 0);

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
            
            if (!aatg_managed_mode_active() && empty($options[$api_key_field])) {
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
                $api_key,
                array('attachment_id' => $item->ID, 'source' => 'bulk')
            );

            // An account-level failure (out of credits, not activated, disabled,
            // bad token) will reject every remaining image in exactly the same
            // way. Stop the run and tell the user why, instead of grinding
            // through the whole library making doomed requests.
            if (!$result['success']
                && !empty($result['code'])
                && function_exists('aatg_is_terminal_managed_error')
                && aatg_is_terminal_managed_error($result['code'])
            ) {
                $this->finish_processing();

                return new WP_REST_Response(array(
                    'status'        => 'stopped',
                    'code'          => $result['code'],
                    'message'       => $result['message'],
                    'upgrade_url'   => isset($result['upgrade_url']) ? $result['upgrade_url'] : '',
                    'current'       => $current,
                    'total'         => $total,
                    'is_processing' => false,
                ), 200);
            }

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            $alt_text = $result['alt_text'];

            // Save the generated alt text (with add-on hooks).
            aatg_save_generated_alt_text($item->ID, $alt_text, array('source' => 'bulk'));
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
            update_option('aatg_processing_skipped', 0);
            return;
        }

        $args = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $batch_size,
            'offset'         => get_option('aatg_processing_current', 0)
        );

        // A Media Library bulk action records the exact selection. Page through
        // that list rather than the whole library, so we only ever touch the
        // images the user actually picked. Mirrors process_next_image().
        $bulk_ids = get_transient('aatg_bulk_generation_ids');

        if ($bulk_ids && is_array($bulk_ids)) {
            $args['post__in'] = $bulk_ids;
            $args['orderby']  = 'post__in';
        } elseif (!$this->rewrite_all) {
            // If not rewriting all, fetch images without alt text
            $ids = $this->get_images_without_alt_text_ids();
            if (empty($ids)) {
                $this->finish_processing();
                return;
            }
            $args['post__in'] = $ids;

            // This candidate list SHRINKS as images gain alt text, so paging it
            // with a progress-based offset walks off the front of the queue and
            // silently skips images. Step past only the ones we couldn't
            // process — everything successful drops out of the list by itself.
            $args['offset'] = get_option('aatg_processing_skipped', 0);
        }

        $media_items = get_posts($args);

        if (empty($media_items)) {
            $this->finish_processing();
            return;
        }

        $admin_instance = AATG_Text_Generator_Admin::get_instance();
        $current = get_option('aatg_processing_current', 0);
        $total = get_option('aatg_processing_total', 0);

        foreach ($media_items as $item) {
            if (!get_option('aatg_is_processing', false)) {
                return;
            }

            // `current` is both the progress counter and (for fixed-list runs)
            // the query offset, so it advances for every image taken off the
            // queue — including failures. Advancing only on success re-fetched
            // the same failing image on every batch and the run never moved on.
            $current++;
            $saved = false;

            $image_url = $admin_instance->get_image_url_by_size($item->ID, 'thumbnail');

            if ($image_url) {
                try {
                    $alt_text = $admin_instance->generate_alt_text_with_ai(
                        $image_url,
                        array('attachment_id' => $item->ID, 'source' => 'bulk')
                    );

                    if ($alt_text) {
                        // Route through the shared saver so add-on hooks and the
                        // Title/Caption/Description mirroring apply here too.
                        $admin_instance->save_generated_alt_text(
                            $item->ID,
                            $alt_text,
                            array('source' => 'bulk')
                        );
                        $saved = true;
                    }
                } catch (Exception $e) {
                    // Leave $saved false; counted as skipped below.
                }
            }

            // generate_alt_text_with_ai() flattens every failure to an empty
            // string, so the reason has to come from the transient the managed
            // client records. Same rule as the REST path: an account-level dead
            // end stops the run rather than repeating for every image left.
            if (!$saved && function_exists('aatg_is_terminal_managed_error')) {
                $last = get_transient('aatg_managed_last_error');
                if ($last && aatg_is_terminal_managed_error($last)) {
                    update_option('aatg_processing_current', $current);
                    $this->finish_processing();
                    return; // Deliberately not rescheduled.
                }
            }

            // An image we couldn't process stays in the "missing alt text" query,
            // so that run needs to step over it explicitly or it would be
            // re-fetched forever.
            if (!$saved) {
                update_option('aatg_processing_skipped', get_option('aatg_processing_skipped', 0) + 1);
            }

            update_option('aatg_processing_current', $current);

            // Check if we've processed all images
            if ($total > 0 && $current >= $total) {
                $this->finish_processing();
                return;
            }
        }

        if (get_option('aatg_is_processing', false)) {
            wp_schedule_single_event(time() + 5, 'ai_process_media_batch', array($batch_size));
        }
	}

	/** Clear all bulk-run state. Terminal step for every finish path. */
	private function finish_processing() {
        update_option('aatg_is_processing', false);
        update_option('aatg_processing_total', 0);
        update_option('aatg_processing_current', 0);
        update_option('aatg_processing_skipped', 0);
        delete_transient('aatg_bulk_generation_ids');
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

        // Never expose the managed-credit token to the frontend; surface a
        // connected flag instead.
        $options['managed_connected'] = !empty($options['managed_token']);
        unset($options['managed_token']);

        // Let the settings UI show the Pro upsell only when Pro isn't installed.
        $options['pro_active'] = defined('AATG_PRO_VERSION');

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
        
        // Remove read-only / server-managed fields the frontend echoes back.
        unset($settings['available_providers'], $settings['default_models'], $settings['managed_connected']);

        // Preserve the managed-credit token: it's set via /managed/connect and is
        // never sent by the settings UI, so don't let a normal save wipe it.
        $existing = get_option('aatg_text_generator_options', array());
        if (empty($settings['managed_token']) && !empty($existing['managed_token'])) {
            $settings['managed_token'] = $existing['managed_token'];
        }

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
            
            if (!aatg_managed_mode_active() && empty($options[$api_key_field])) {
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
