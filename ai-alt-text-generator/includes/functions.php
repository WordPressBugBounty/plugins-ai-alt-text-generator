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
			'prompt' => 'Create a SEO optimized alt text for this image. Don\'t include quotes and keep it informative and concise.',
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
