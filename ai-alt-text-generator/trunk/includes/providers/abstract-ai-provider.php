<?php
/**
 * Abstract AI Provider Class
 *
 * @since 2.1.0
 * @package AI_Alt_Text_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract base class for AI providers
 */
abstract class AATG_Abstract_AI_Provider {
    
    /**
     * Provider name/identifier
     */
    abstract public function get_name();
    
    /**
     * Provider display name
     */
    abstract public function get_display_name();
    
    /**
     * Validate API key for this provider
     * 
     * @param string $api_key
     * @return array Array with 'valid' boolean and 'message' string
     */
    abstract public function validate_api_key($api_key);
    
    /**
     * Generate alt text for image
     * 
     * @param string $image_base64 Base64 encoded image
     * @param string $prompt Custom prompt
     * @param string $language Target language
     * @param string $api_key API key
     * @return array Array with 'success' boolean, 'alt_text' string, and 'message' string
     */
    abstract public function generate_alt_text($image_base64, $prompt, $language, $api_key);
    
    /**
     * Get supported models for this provider
     * 
     * @return array
     */
    abstract public function get_supported_models();
    
    /**
     * Get default model for this provider
     * 
     * @return string
     */
    abstract public function get_default_model();
    
    /**
     * Get help URL for getting API key
     *
     * @return string
     */
    abstract public function get_api_key_help_url();

    /**
     * Fetch the live model catalog from the provider's API.
     *
     * Concrete (not abstract) on purpose: third-party providers registered via
     * the aatg_providers filter keep working without changes — they simply have
     * no live lookup and fall back to their bundled get_supported_models().
     *
     * @since 2.6.5
     * @param string $api_key
     * @return array id => display label, or empty array when unavailable.
     */
    protected function fetch_model_catalog($api_key) {
        return array();
    }

    /**
     * Get the saved plugin options.
     *
     * @since 2.6.5
     * @return array
     */
    protected function get_options() {
        $options = get_option('aatg_text_generator_options', array());
        return is_array($options) ? $options : array();
    }

    /**
     * Get the stored API key for this provider.
     *
     * @since 2.6.5
     * @return string
     */
    protected function get_stored_api_key() {
        $options = $this->get_options();
        $field = $this->get_name() . '_key';
        return isset($options[$field]) ? (string) $options[$field] : '';
    }

    /**
     * Transient name for this provider's cached model catalog.
     *
     * Keyed on a hash of the API key so rotating credentials re-fetches
     * instead of serving the previous account's list.
     *
     * @since 2.6.5
     * @param string $api_key
     * @return string
     */
    protected function get_model_cache_key($api_key) {
        return 'aatg_models_' . $this->get_name() . '_' . substr(md5((string) $api_key), 0, 12);
    }

    /**
     * Delete the cached model catalog for this provider.
     *
     * Called when the provider's API key changes.
     *
     * @since 2.6.5
     */
    public function flush_model_cache() {
        $pointer = get_option('aatg_models_cache_' . $this->get_name(), '');
        if (!empty($pointer)) {
            delete_transient($pointer);
            delete_option('aatg_models_cache_' . $this->get_name());
        }
    }

    /**
     * Collapse a dated model snapshot id to its stable alias.
     *
     * claude-haiku-4-5-20251001 => claude-haiku-4-5
     * gpt-4o-2024-08-06         => gpt-4o
     *
     * @since 2.6.5
     * @param string $id
     * @return string
     */
    protected function to_alias($id) {
        $id = preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $id); // OpenAI style
        $id = preg_replace('/-\d{8}$/', '', $id);             // Anthropic style
        return $id;
    }

    /**
     * Get the models available right now, preferring the live catalog.
     *
     * Behavior:
     *  - 12-hour transient cache per provider per key
     *  - a failed fetch returns the bundled list and writes NO transient,
     *    so a bad-key-then-good-key sequence never serves an empty list
     *
     * @since 2.6.5
     * @param string|null $api_key Key to use; defaults to the stored key.
     * @param bool        $force   Bypass the cache.
     * @return array { 'models' => array id => label, 'live' => bool }
     */
    public function get_available_models($api_key = null, $force = false) {
        if (null === $api_key) {
            $api_key = $this->get_stored_api_key();
        }

        $bundled = array('models' => $this->get_supported_models(), 'live' => false);

        if (empty($api_key)) {
            return $bundled;
        }

        $cache_key = $this->get_model_cache_key($api_key);

        if (!$force) {
            $cached = get_transient($cache_key);
            if (is_array($cached) && !empty($cached)) {
                return array('models' => $cached, 'live' => true);
            }
        }

        $catalog = $this->fetch_model_catalog($api_key);

        if (empty($catalog)) {
            // Never cache a failure.
            return $bundled;
        }

        set_transient($cache_key, $catalog, 12 * HOUR_IN_SECONDS);
        update_option('aatg_models_cache_' . $this->get_name(), $cache_key, false);

        return array('models' => $catalog, 'live' => true);
    }

    /**
     * Whether a model id is served, per the given catalog.
     *
     * A saved id counts as available if it is listed verbatim, OR any listed
     * id starts with it (so the alias claude-haiku-4-5 matches the catalog
     * entry claude-haiku-4-5-20251001).
     *
     * @since 2.6.5
     * @param string $model
     * @param array  $catalog id => label
     * @return bool
     */
    protected function model_is_available($model, $catalog) {
        if (isset($catalog[$model])) {
            return true;
        }
        foreach (array_keys($catalog) as $id) {
            if (0 === strpos($id, $model)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Resolve which model a request should actually use.
     *
     * Uses the saved 'model' setting when it belongs to this provider and is
     * still served; otherwise the provider default. If the live catalog can't
     * be fetched we fail open — "could not verify" is not "model is bad".
     *
     * @since 2.6.5
     * @return string
     */
    public function resolve_model() {
        $options = $this->get_options();
        $default = $this->get_default_model();

        $saved = isset($options['model']) ? trim((string) $options['model']) : '';
        $active_provider = isset($options['ai_provider']) ? $options['ai_provider'] : '';

        // The 'model' setting is a single field shared by both providers —
        // don't send an OpenAI id to Anthropic after a provider switch.
        if ('' === $saved || $active_provider !== $this->get_name()) {
            return $default;
        }

        $available = $this->get_available_models();

        if (!$available['live']) {
            // Could not verify: keep the saved value rather than silently
            // overriding a valid configuration on a network blip.
            return $saved;
        }

        return $this->model_is_available($saved, $available['models']) ? $saved : $default;
    }

    /**
     * Common method to make HTTP requests
     * 
     * @param string $url
     * @param array $headers
     * @param string $body
     * @param string $method
     * @param int $timeout
     * @return array|WP_Error
     */
    protected function make_request($url, $headers = array(), $body = '', $method = 'POST', $timeout = 30) {
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => $timeout,
        );
        
        if (!empty($body)) {
            $args['body'] = $body;
        }
        
        if ($method === 'POST') {
            return wp_remote_post($url, $args);
        } else {
            return wp_remote_get($url, $args);
        }
    }
    
    /**
     * Common method to handle API response
     * 
     * @param array|WP_Error $response
     * @param string $provider_name
     * @return array
     */
    protected function handle_response($response, $provider_name = '') {
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $provider_name . ' API error: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            // Decode the error body too, so callers can surface the
            // provider's own error message instead of a raw JSON string.
            return array(
                'success' => false,
                'message' => $provider_name . ' API error: ' . $response_body,
                'data' => json_decode($response_body, true),
                'response_code' => $response_code
            );
        }
        
        return array(
            'success' => true,
            'data' => json_decode($response_body, true),
            'raw_body' => $response_body
        );
    }
} 