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
