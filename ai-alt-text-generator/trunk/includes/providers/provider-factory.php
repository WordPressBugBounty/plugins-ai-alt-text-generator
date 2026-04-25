<?php
/**
 * AI Provider Factory
 *
 * @since 2.1.0
 * @package AI_Alt_Text_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once 'openai-provider.php';
require_once 'anthropic-provider.php';

/**
 * Factory class for managing AI providers
 */
class AATG_Provider_Factory {
    
    /**
     * Available providers
     * 
     * @var array
     */
    private static $providers = array();
    
    /**
     * Initialize all providers
     */
    public static function init() {
        if (empty(self::$providers)) {
            self::$providers = array(
                'openai' => new AATG_OpenAI_Provider(),
                'anthropic' => new AATG_Anthropic_Provider(),
                // Future providers can be added here
                // 'google' => new AATG_Google_Provider(),
            );
        }
    }
    
    /**
     * Get all available providers
     * 
     * @return array
     */
    public static function get_providers() {
        self::init();
        return self::$providers;
    }
    
    /**
     * Get provider by name
     * 
     * @param string $provider_name
     * @return AATG_Abstract_AI_Provider|null
     */
    public static function get_provider($provider_name) {
        self::init();
        return isset(self::$providers[$provider_name]) ? self::$providers[$provider_name] : null;
    }
    
    /**
     * Get provider options for select field
     * 
     * @return array
     */
    public static function get_provider_options() {
        self::init();
        $options = array();
        
        foreach (self::$providers as $provider) {
            $options[$provider->get_name()] = $provider->get_display_name();
        }
        
        return $options;
    }
    
    /**
     * Check if provider exists
     * 
     * @param string $provider_name
     * @return bool
     */
    public static function provider_exists($provider_name) {
        self::init();
        return isset(self::$providers[$provider_name]);
    }
    
    /**
     * Get default provider name
     * 
     * @return string
     */
    public static function get_default_provider() {
        return 'openai'; // Default to OpenAI for backward compatibility
    }
    
    /**
     * Validate API key for specific provider
     * 
     * @param string $provider_name
     * @param string $api_key
     * @return array
     */
    public static function validate_api_key($provider_name, $api_key) {
        $provider = self::get_provider($provider_name);
        
        if (!$provider) {
            return array(
                'valid' => false,
                'message' => 'Provider not found: ' . $provider_name
            );
        }
        
        return $provider->validate_api_key($api_key);
    }
    
    /**
     * Generate alt text using specified provider
     * 
     * @param string $provider_name
     * @param string $image_base64
     * @param string $prompt
     * @param string $language
     * @param string $api_key
     * @return array
     */
    public static function generate_alt_text($provider_name, $image_base64, $prompt, $language, $api_key) {
        $provider = self::get_provider($provider_name);
        
        if (!$provider) {
            return array(
                'success' => false,
                'alt_text' => '',
                'message' => 'Provider not found: ' . $provider_name
            );
        }
        
        return $provider->generate_alt_text($image_base64, $prompt, $language, $api_key);
    }
    
    /**
     * Get help URL for provider's API key
     * 
     * @param string $provider_name
     * @return string
     */
    public static function get_api_key_help_url($provider_name) {
        $provider = self::get_provider($provider_name);
        
        if (!$provider) {
            return '';
        }
        
        return $provider->get_api_key_help_url();
    }
    
    /**
     * Get supported models for provider
     * 
     * @param string $provider_name
     * @return array
     */
    public static function get_supported_models($provider_name) {
        $provider = self::get_provider($provider_name);
        
        if (!$provider) {
            return array();
        }
        
        return $provider->get_supported_models();
    }
    
    /**
     * Get default model for provider
     * 
     * @param string $provider_name
     * @return string
     */
    public static function get_default_model($provider_name) {
        $provider = self::get_provider($provider_name);
        
        if (!$provider) {
            return '';
        }
        
        return $provider->get_default_model();
    }
} 