<?php
/**
 * Get the Plugin Default Options.
 *
 * @since 1.0.0
 *
 * @param null
 *
 * @return array Default Options
 *
 * @author     codersantosh <codersantosh@gmail.com>
 *
 */
if ( ! function_exists( 'aatg_text_generator_default_options' ) ) :
	function aatg_text_generator_default_options() {
		$default_theme_options = array(
			'ai_provider' => 'openai',
			'openai_key' => '',
			'anthropic_key' => '',
			'on_upload_alt_text' => false,
			'all_alt_text' => false,
			'set_title' => false,
			'set_caption' => false,
			'set_description' => false,
			'managed_mode' => false,
			'managed_email' => '',
			'managed_token' => '',
			'prompt' => 'Write concise, descriptive alt text for this image that conveys its purpose and key content for screen-reader users and SEO. Use a single sentence under 125 characters. Do not begin with "image of", "photo of", or "picture of", and do not use quotation marks.',
			'language' => 'english',
		);

		return apply_filters( 'aatg_text_generator_default_options', $default_theme_options );
	}
endif;

/**
 * Get the Plugin Saved Options.
 *
 * @since 1.0.0
 *
 * @param string $key optional option key
 *
 * @return mixed All Options Array Or Options Value
 *
 * @author     codersantosh <codersantosh@gmail.com>
 *
 */
if ( ! function_exists( 'aatg_text_generator_get_options' ) ) :
	function aatg_text_generator_get_options( $key = '' ) {
		$options         = get_option( 'aatg_text_generator_options' );
		$default_options = aatg_text_generator_default_options();

		if ( ! empty( $key ) ) {
			if ( isset( $options[ $key ] ) ) {
				return $options[ $key ];
			}
			return isset( $default_options[ $key ] ) ? $default_options[ $key ] : false;
		} else {
			if ( ! is_array( $options ) ) {
				$options = array();
			}
			return array_merge( $default_options, $options );
		}
	}
endif;

/**
 * Alt-text coverage stats for the media library (cached).
 *
 * Direct COUNT queries so large libraries don't load every attachment. Cached
 * briefly since it powers the dashboard widget on frequent admin loads.
 *
 * @since 2.5.4
 * @return array{ total:int, with_alt:int, missing:int, percent:int }
 */
if ( ! function_exists( 'aatg_get_coverage_stats' ) ) :
	function aatg_get_coverage_stats() {
		$cached = get_transient( 'aatg_coverage_stats' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		global $wpdb;
		$total = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'attachment' AND post_status = 'inherit' AND post_mime_type LIKE 'image/%'"
		);
		$missing = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} m ON ( p.ID = m.post_id AND m.meta_key = '_wp_attachment_image_alt' )
			 WHERE p.post_type = 'attachment' AND p.post_status = 'inherit' AND p.post_mime_type LIKE 'image/%'
			   AND ( m.meta_id IS NULL OR m.meta_value = '' )"
		);
		$with  = max( 0, $total - $missing );
		$stats = array(
			'total'    => $total,
			'with_alt' => $with,
			'missing'  => $missing,
			'percent'  => $total > 0 ? (int) round( ( $with / $total ) * 100 ) : 100,
		);
		set_transient( 'aatg_coverage_stats', $stats, 15 * MINUTE_IN_SECONDS );
		return $stats;
	}
endif;

/**
 * Persist a generated alt text value for an attachment, applying add-on hooks.
 *
 * Centralises the "filter then save then notify" sequence so every generation
 * path (single image, bulk, on-upload, REST, CLI) exposes the same extension
 * points to add-ons:
 *
 *  - filter `aatg_alt_text`     : adjust the alt text per attachment before saving.
 *  - action `aatg_after_generate`: react after the alt text is saved (SEO sync, logging…).
 *
 * @since 2.3.0
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $alt_text      Generated alt text.
 * @param array  $context       Request context (e.g. 'source').
 * @return string The alt text that was saved (possibly filtered); empty string if nothing saved.
 */
if ( ! function_exists( 'aatg_save_generated_alt_text' ) ) :
	function aatg_save_generated_alt_text( $attachment_id, $alt_text, $context = array() ) {
		$context = array_merge( array( 'attachment_id' => $attachment_id ), $context );

		/**
		 * Filter the alt text for a specific attachment just before it is saved.
		 *
		 * @since 2.3.0
		 *
		 * @param string $alt_text      The generated alt text.
		 * @param int    $attachment_id Attachment ID.
		 * @param array  $context       Request context.
		 */
		$alt_text = apply_filters( 'aatg_alt_text', $alt_text, $attachment_id, $context );

		if ( '' === (string) $alt_text ) {
			return $alt_text;
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );

		// Optionally mirror the alt text into the attachment's Title / Caption /
		// Description when those settings are enabled.
		$opts        = aatg_text_generator_get_options();
		$post_update = array();
		if ( ! empty( $opts['set_title'] ) ) {
			$post_update['post_title'] = $alt_text;
		}
		if ( ! empty( $opts['set_caption'] ) ) {
			$post_update['post_excerpt'] = $alt_text;
		}
		if ( ! empty( $opts['set_description'] ) ) {
			$post_update['post_content'] = $alt_text;
		}
		if ( ! empty( $post_update ) ) {
			$post_update['ID'] = $attachment_id;
			wp_update_post( $post_update );
		}

		/**
		 * Fires after alt text has been generated and saved for an attachment.
		 *
		 * Integration point for SEO-plugin sync, coverage tracking, logging, etc.
		 *
		 * @since 2.3.0
		 *
		 * @param int    $attachment_id Attachment ID.
		 * @param string $alt_text      The alt text that was saved.
		 * @param array  $context       Request context.
		 */
		do_action( 'aatg_after_generate', $attachment_id, $alt_text, $context );

		return $alt_text;
	}
endif;

/**
 * Get the SEO focus keyphrase for a post from common SEO plugins (Yoast,
 * Rank Math, SEOPress). Filterable so other SEO plugins (e.g. AIOSEO) can be added.
 *
 * @since 2.4.0
 * @param int $post_id
 * @return string
 */
if ( ! function_exists( 'aatg_get_focus_keyphrase' ) ) :
	function aatg_get_focus_keyphrase( $post_id ) {
		$keyphrase = '';
		$meta_keys = array(
			'_yoast_wpseo_focuskw',         // Yoast SEO
			'rank_math_focus_keyword',      // Rank Math (may be a comma list)
			'_seopress_analysis_target_kw', // SEOPress (may be a comma list)
		);
		foreach ( $meta_keys as $key ) {
			$val = get_post_meta( $post_id, $key, true );
			if ( ! empty( $val ) ) {
				$parts     = explode( ',', (string) $val );
				$keyphrase = trim( wp_strip_all_tags( $parts[0] ) );
				if ( '' !== $keyphrase ) {
					break;
				}
			}
		}
		/**
		 * Filter the detected focus keyphrase (e.g. to add AIOSEO support).
		 *
		 * @since 2.4.0
		 * @param string $keyphrase
		 * @param int    $post_id
		 */
		return apply_filters( 'aatg_focus_keyphrase', $keyphrase, $post_id );
	}
endif;

/**
 * Enrich the generation prompt with page context + the SEO focus keyphrase of
 * the post the image belongs to. Hooked onto the plugin's own
 * `aatg_generate_prompt` filter, so it composes with other prompt filters (e.g.
 * the Pro add-on's WooCommerce context). Disable via `aatg_enable_context_enrichment`.
 *
 * @since 2.4.0
 * @param string $prompt
 * @param array  $context Expects 'attachment_id'.
 * @return string
 */
if ( ! function_exists( 'aatg_enrich_prompt_with_context' ) ) :
	function aatg_enrich_prompt_with_context( $prompt, $context ) {
		if ( ! apply_filters( 'aatg_enable_context_enrichment', true ) ) {
			return $prompt;
		}
		$attachment_id = isset( $context['attachment_id'] ) ? (int) $context['attachment_id'] : 0;
		if ( ! $attachment_id ) {
			return $prompt;
		}
		$post_id = (int) wp_get_post_parent_id( $attachment_id );
		if ( ! $post_id ) {
			return $prompt;
		}

		$extra = array();
		$title = wp_strip_all_tags( get_the_title( $post_id ) );
		if ( '' !== $title ) {
			$extra[] = 'For context, this image appears on a page titled "' . $title . '".';
		}
		$keyphrase = aatg_get_focus_keyphrase( $post_id );
		if ( '' !== $keyphrase ) {
			$extra[] = 'If it fits naturally, incorporate the keyphrase "' . $keyphrase . '" (do not keyword-stuff).';
		}

		if ( empty( $extra ) ) {
			return $prompt;
		}
		return $prompt . ' ' . implode( ' ', $extra );
	}
endif;
add_filter( 'aatg_generate_prompt', 'aatg_enrich_prompt_with_context', 10, 2 );

/**
 * Return base64-encoded image data for an attachment at a sensible size.
 *
 * Prefers a downscaled size (default "large", ~1024px) over the full-size
 * original — better cost/latency for vision models and higher quality than the
 * tiny thumbnail some paths used previously. Falls back to the original file.
 *
 * @since 2.4.0
 * @param int    $attachment_id
 * @param string $size
 * @return string Base64 string, or '' on failure.
 */
if ( ! function_exists( 'aatg_get_image_base64_for_attachment' ) ) :
	function aatg_get_image_base64_for_attachment( $attachment_id, $size = 'large' ) {
		$size = apply_filters( 'aatg_image_size', $size, $attachment_id );
		$path = '';

		$src = wp_get_attachment_image_src( $attachment_id, $size );
		if ( $src && ! empty( $src[0] ) ) {
			$upload    = wp_upload_dir();
			$candidate = str_replace( $upload['baseurl'], $upload['basedir'], $src[0] );
			if ( file_exists( $candidate ) ) {
				$path = $candidate;
			}
		}
		if ( '' === $path ) {
			$full = get_attached_file( $attachment_id );
			if ( $full && file_exists( $full ) ) {
				$path = $full;
			}
		}
		if ( '' === $path ) {
			return '';
		}

		$data = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $data ) {
			return '';
		}
		return base64_encode( $data ); // phpcs:ignore
	}
endif;

/* -------------------------------------------------------------------------- */
/* Managed credits (optional, no-API-key mode powered by the store)            */
/* -------------------------------------------------------------------------- */

/**
 * Base URL of the managed-credit store. Overridable via the AATG_STORE_URL
 * constant or the `aatg_store_url` filter.
 *
 * @since 2.5.1
 * @return string
 */
if ( ! function_exists( 'aatg_store_url' ) ) :
	function aatg_store_url() {
		$url = defined( 'AATG_STORE_URL' ) ? AATG_STORE_URL : 'https://store.lessbutmore.ai';
		return untrailingslashit( apply_filters( 'aatg_store_url', $url ) );
	}
endif;

/**
 * Whether managed-credit mode is active (enabled + connected).
 *
 * @since 2.5.1
 * @return bool
 */
if ( ! function_exists( 'aatg_managed_mode_active' ) ) :
	function aatg_managed_mode_active() {
		$o = aatg_text_generator_get_options();
		return ! empty( $o['managed_mode'] ) && ! empty( $o['managed_token'] );
	}
endif;

/**
 * Generate alt text via the managed store (no provider API key needed).
 *
 * @since 2.5.1
 * @return string|WP_Error Alt text, or WP_Error on failure.
 */
if ( ! function_exists( 'aatg_managed_generate' ) ) :
	function aatg_managed_generate( $token, $image_base64, $prompt, $language, $mime = '' ) {
		$resp = wp_remote_post( aatg_store_url() . '/api/generate', array(
			'timeout' => 60,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array(
				'token'     => $token,
				'image'     => $image_base64,
				'mime_type' => $mime ? $mime : 'image/jpeg',
				'prompt'    => $prompt,
				'language'  => $language,
			) ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$code = wp_remote_retrieve_response_code( $resp );
		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( 200 === $code && ! empty( $data['alt_text'] ) ) {
			if ( isset( $data['credits_remaining'] ) ) {
				set_transient( 'aatg_managed_credits', (int) $data['credits_remaining'], HOUR_IN_SECONDS );
				delete_transient( 'aatg_managed_last_error' );
			}
			return $data['alt_text'];
		}
		$err = isset( $data['error'] ) ? $data['error'] : 'generation_failed';
		set_transient( 'aatg_managed_last_error', $err, HOUR_IN_SECONDS );
		return new WP_Error( 'aatg_managed', $err );
	}
endif;

/**
 * Register (or re-connect) a free managed-credit account for this site.
 *
 * @since 2.5.1
 * @return array|WP_Error Store response (token, status, plan, credits) or WP_Error.
 */
if ( ! function_exists( 'aatg_managed_register' ) ) :
	function aatg_managed_register( $email, $site_url ) {
		$resp = wp_remote_post( aatg_store_url() . '/api/credits/register', array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array( 'email' => $email, 'site_url' => $site_url ) ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( 200 === wp_remote_retrieve_response_code( $resp ) && ! empty( $data['token'] ) ) {
			return $data;
		}
		return new WP_Error( 'aatg_managed', isset( $data['error'] ) ? $data['error'] : 'register_failed' );
	}
endif;

/**
 * Fetch the current plan + credit balance for a managed token.
 *
 * @since 2.5.1
 * @return array|WP_Error
 */
if ( ! function_exists( 'aatg_managed_status' ) ) :
	function aatg_managed_status( $token ) {
		$resp = wp_remote_post( aatg_store_url() . '/api/credits/status', array(
			'timeout' => 20,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array( 'token' => $token ) ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( 200 === wp_remote_retrieve_response_code( $resp ) && ! empty( $data['ok'] ) ) {
			return $data;
		}
		return new WP_Error( 'aatg_managed', isset( $data['error'] ) ? $data['error'] : 'status_failed' );
	}
endif;

/**
 * Map a managed-store error code to a human-readable, translatable message.
 *
 * The store's /api/generate returns short reason codes (not_found, pending,
 * disabled, no_credits) or a generation error string; surface something the
 * site owner can act on instead of a raw code.
 *
 * @since 2.5.4
 * @param string $code Raw error code/message from the store.
 * @return string Friendly message.
 */
if ( ! function_exists( 'aatg_managed_error_message' ) ) :
	function aatg_managed_error_message( $code ) {
		switch ( (string) $code ) {
			case 'no_credits':
				return __( "You're out of managed credits. Upgrade your plan to keep generating alt text.", 'ai-alt-text-generator' );
			case 'pending':
				return __( 'Your managed account is not activated yet. Check your email for the activation link, or reconnect in settings.', 'ai-alt-text-generator' );
			case 'disabled':
				return __( 'Your managed account is disabled. Please contact support.', 'ai-alt-text-generator' );
			case 'not_found':
				return __( 'Your managed connection is invalid. Reconnect the managed credits service in settings.', 'ai-alt-text-generator' );
			case '':
			case 'generation_failed':
				return __( 'The managed credits service could not generate alt text for this image. Please try again.', 'ai-alt-text-generator' );
			default:
				return sprintf(
					/* translators: %s: raw error message from the managed service. */
					__( 'Managed credits service error: %s', 'ai-alt-text-generator' ),
					$code
				);
		}
	}
endif;

/**
 * Track successful generations: powers the "leave a review" prompt and keeps the
 * cached coverage stats fresh. Hooked on the plugin's own post-save action.
 *
 * @since 2.5.4
 */
if ( ! function_exists( 'aatg_track_generation' ) ) :
	function aatg_track_generation( $attachment_id = 0, $alt_text = '', $context = array() ) {
		delete_transient( 'aatg_coverage_stats' );
		update_option( 'aatg_generated_count', (int) get_option( 'aatg_generated_count', 0 ) + 1, false );
	}
endif;
add_action( 'aatg_after_generate', 'aatg_track_generation', 10, 3 );

/**
 * Auto-connect a site to managed credits so alt text works out of the box.
 *
 * Managed credits are the default: on a fresh install (or an existing install
 * with no provider key), grab a free trial account (25 credits, no email) on the
 * next admin load. Skips sites that use their own API key or explicitly
 * disconnected. Best-effort with backoff so a store outage never slows wp-admin.
 *
 * @since 2.5.4
 */
if ( ! function_exists( 'aatg_maybe_autoconnect_managed' ) ) :
	function aatg_maybe_autoconnect_managed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o = aatg_text_generator_get_options();
		// Already connected, using a BYO key, or the user opted out — respect it.
		if ( ! empty( $o['managed_token'] ) ) {
			return;
		}
		if ( ! empty( $o['openai_key'] ) || ! empty( $o['anthropic_key'] ) ) {
			return;
		}
		if ( ! empty( $o['managed_optout'] ) ) {
			return;
		}
		// A paid plan exists for this site; the user must paste their token.
		if ( ! empty( $o['managed_awaiting_token'] ) ) {
			return;
		}
		// Throttle attempts if the store is unreachable.
		if ( get_transient( 'aatg_autoconnect_backoff' ) ) {
			return;
		}
		// Optimistic lock: set the backoff BEFORE the network call so a hung store
		// can't make every admin page load block on a fresh 30s request.
		set_transient( 'aatg_autoconnect_backoff', 1, 15 * MINUTE_IN_SECONDS );
		$res = aatg_managed_register( '', home_url() );
		if ( is_wp_error( $res ) ) {
			// A paid plan already exists for this site — stop auto-registering and
			// let the settings screen prompt the user to paste their token.
			if ( 'paid_account_exists' === $res->get_error_message() ) {
				$o['managed_awaiting_token'] = true;
				update_option( 'aatg_text_generator_options', $o );
				set_transient( 'aatg_autoconnect_backoff', 1, WEEK_IN_SECONDS );
			}
			return;
		}
		$o['managed_token'] = $res['token'];
		$o['managed_ref']   = isset( $res['account_ref'] ) ? $res['account_ref'] : '';
		$o['managed_mode']  = true;
		update_option( 'aatg_text_generator_options', $o );
		delete_transient( 'aatg_autoconnect_backoff' );
	}
endif;
add_action( 'admin_init', 'aatg_maybe_autoconnect_managed' );

/**
 * Short-circuit generation through the managed store when managed mode is on.
 * Hooked on the plugin's own `aatg_pre_generate_alt_text` filter.
 *
 * Returns the standard result array — array( 'success', 'alt_text', 'message' ) —
 * that Provider_Factory::generate_alt_text() and every caller expect. Returning a
 * bare string here silently breaks every consumer (they index $result['success']),
 * which is why managed generation never completed before 2.5.4.
 *
 * @since 2.5.1
 */
if ( ! function_exists( 'aatg_managed_pre_generate' ) ) :
	function aatg_managed_pre_generate( $pre, $image_base64, $prompt, $language, $context ) {
		if ( null !== $pre || ! aatg_managed_mode_active() ) {
			return $pre;
		}
		$o    = aatg_text_generator_get_options();
		$mime = ! empty( $context['attachment_id'] ) ? get_post_mime_type( (int) $context['attachment_id'] ) : '';
		$alt  = aatg_managed_generate( $o['managed_token'], $image_base64, $prompt, $language, $mime );

		if ( is_wp_error( $alt ) ) {
			// Non-null array short-circuits the provider path with a proper failure
			// result, so callers surface a real message instead of falling through
			// to the keyless provider (which would also fail).
			return array(
				'success'  => false,
				'alt_text' => '',
				'message'  => aatg_managed_error_message( $alt->get_error_message() ),
			);
		}

		$alt = (string) $alt;
		if ( '' === $alt ) {
			return array(
				'success'  => false,
				'alt_text' => '',
				'message'  => aatg_managed_error_message( '' ),
			);
		}

		return array(
			'success'  => true,
			'alt_text' => $alt,
			'message'  => '',
		);
	}
endif;
add_filter( 'aatg_pre_generate_alt_text', 'aatg_managed_pre_generate', 10, 5 );
