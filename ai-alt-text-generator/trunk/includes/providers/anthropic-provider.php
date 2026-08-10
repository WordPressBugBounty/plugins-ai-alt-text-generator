<?php
/**
 * Anthropic Provider Class
 *
 * @since 2.1.0
 * @package AI_Alt_Text_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once 'abstract-ai-provider.php';

/**
 * Anthropic (Claude) provider implementation
 */
class AATG_Anthropic_Provider extends AATG_Abstract_AI_Provider {
    
    /**
     * Provider name/identifier
     */
    public function get_name() {
        return 'anthropic';
    }
    
    /**
     * Provider display name
     */
    public function get_display_name() {
        return 'Anthropic';
    }
    
    /**
     * Validate API key for Anthropic
     * 
     * @param string $api_key
     * @return array
     */
    public function validate_api_key($api_key) {
        if (empty($api_key)) {
            return array(
                'valid' => false,
                'message' => 'API key is required'
            );
        }

        // Validate with the models list endpoint: it authenticates without
        // naming a model, so a retired model can never be misreported as a
        // bad credential. (This mirrors how the OpenAI provider validates.)
        $response = $this->make_request(
            'https://api.anthropic.com/v1/models?limit=1',
            array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            '',
            'GET'
        );

        $result = $this->handle_response($response, 'Anthropic');
        
        if ($result['success']) {
            return array(
                'valid' => true,
                'message' => 'API key is valid'
            );
        } else {
            $data = isset($result['data']) ? $result['data'] : array();
            $message = isset($data['error']['message']) ? $data['error']['message'] : 'Invalid API key';
            return array(
                'valid' => false,
                'message' => $message
            );
        }
    }
    
    /**
     * Generate alt text using Anthropic Claude
     * 
     * @param string $image_base64
     * @param string $prompt
     * @param string $language
     * @param string $api_key
     * @return array
     */
    public function generate_alt_text($image_base64, $prompt, $language, $api_key) {
        try {
            $prompt_with_lang = $prompt . ' Write it in this language: ' . $language;
            
            // Determine image media type (default to jpeg)
            $media_type = 'image/jpeg';
            if (strpos($image_base64, 'data:image/png') === 0) {
                $media_type = 'image/png';
            } elseif (strpos($image_base64, 'data:image/gif') === 0) {
                $media_type = 'image/gif';
            } elseif (strpos($image_base64, 'data:image/webp') === 0) {
                $media_type = 'image/webp';
            }
            
            // Clean base64 data (remove data:image/xxx;base64, prefix if present)
            $clean_base64 = preg_replace('/^data:image\/[^;]+;base64,/', '', $image_base64);
            
            $body = wp_json_encode([
                'model' => $this->resolve_model(),
                'max_tokens' => 100,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $media_type,
                                    'data' => $clean_base64
                                ]
                            ],
                            [
                                'type' => 'text',
                                'text' => $prompt_with_lang
                            ]
                        ]
                    ]
                ]
            ]);

            $response = $this->make_request(
                'https://api.anthropic.com/v1/messages',
                array(
                    'x-api-key' => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json'
                ),
                $body
            );

            $result = $this->handle_response($response, 'Anthropic');
            
            if (!$result['success']) {
                return array(
                    'success' => false,
                    'alt_text' => '',
                    'message' => $result['message']
                );
            }

            $data = $result['data'];
            if (!isset($data['content'][0]['text'])) {
                return array(
                    'success' => false,
                    'alt_text' => '',
                    'message' => 'Invalid response from Anthropic API'
                );
            }

            $alt_text = trim($data['content'][0]['text']);
            
            return array(
                'success' => true,
                'alt_text' => $alt_text,
                'message' => 'Alt text generated successfully'
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'alt_text' => '',
                'message' => 'Error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get supported models
     * 
     * @return array
     */
    public function get_supported_models() {
        // Stable aliases only — dated snapshots get retired on a schedule,
        // aliases keep working when a new snapshot ships.
        return apply_filters( 'aatg_supported_models', array(
            'claude-haiku-4-5' => 'Claude Haiku 4.5 (Cheapest)',
            'claude-sonnet-5' => 'Claude Sonnet 5',
            'claude-opus-5' => 'Claude Opus 5 (Most capable)',
        ), 'anthropic' );
    }

    /**
     * Get default model
     *
     * @return string
     */
    public function get_default_model() {
        /** @since 2.5.1 See aatg_default_model in the OpenAI provider. */
        return apply_filters( 'aatg_default_model', 'claude-sonnet-5', 'anthropic' );
    }

    /**
     * Fetch the live model catalog from Anthropic.
     *
     * Dated snapshot ids are collapsed to their stable alias so the value
     * offered (and stored) keeps working when a new snapshot ships.
     *
     * @since 2.6.5
     * @param string $api_key
     * @return array id => display label
     */
    protected function fetch_model_catalog($api_key) {
        $response = $this->make_request(
            'https://api.anthropic.com/v1/models?limit=100',
            array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            '',
            'GET'
        );

        $result = $this->handle_response($response, 'Anthropic');
        if (!$result['success'] || empty($result['data']['data']) || !is_array($result['data']['data'])) {
            return array();
        }

        $catalog = array();
        foreach ($result['data']['data'] as $entry) {
            if (empty($entry['id'])) {
                continue;
            }
            $alias = $this->to_alias($entry['id']);
            if (isset($catalog[$alias])) {
                continue;
            }
            $catalog[$alias] = !empty($entry['display_name']) ? $entry['display_name'] : $alias;
        }

        return $catalog;
    }
    
    /**
     * Get API key help URL
     * 
     * @return string
     */
    public function get_api_key_help_url() {
        return 'https://docs.anthropic.com/en/api/getting-started';
    }
} 