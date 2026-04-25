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

        // Test with a simple message to validate the key
        $test_body = wp_json_encode([
            'model' => $this->get_default_model(),
            'max_tokens' => 10,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Hello'
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
            $test_body
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
                'model' => $this->get_default_model(),
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
        return array(
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (Cheapest)',
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
            'claude-3-7-sonnet-20250219' => 'Claude 3.7 Sonnet'
        );
    }
    
    /**
     * Get default model
     * 
     * @return string
     */
    public function get_default_model() {
        return 'claude-3-haiku-20240307';
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