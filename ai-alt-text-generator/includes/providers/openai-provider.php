<?php
/**
 * OpenAI Provider Class
 *
 * @since 2.1.0
 * @package AI_Alt_Text_Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once 'abstract-ai-provider.php';

/**
 * OpenAI provider implementation
 */
class AATG_OpenAI_Provider extends AATG_Abstract_AI_Provider {
    
    /**
     * Provider name/identifier
     */
    public function get_name() {
        return 'openai';
    }
    
    /**
     * Provider display name
     */
    public function get_display_name() {
        return 'OpenAI';
    }
    
    /**
     * Validate API key for OpenAI
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

        $response = $this->make_request(
            'https://api.openai.com/v1/models',
            array('Authorization' => 'Bearer ' . $api_key),
            '',
            'GET'
        );

        $result = $this->handle_response($response, 'OpenAI');
        
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
     * Generate alt text using OpenAI
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
            
            $body = wp_json_encode([
                'model' => $this->resolve_model(),
                'temperature' => 0.6,
                'max_tokens' => 100,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt_with_lang,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,' . $image_base64
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $response = $this->make_request(
                'https://api.openai.com/v1/chat/completions',
                array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ),
                $body
            );

            $result = $this->handle_response($response, 'OpenAI');
            
            if (!$result['success']) {
                return array(
                    'success' => false,
                    'alt_text' => '',
                    'message' => $result['message']
                );
            }

            $data = $result['data'];
            if (!isset($data['choices'][0]['message']['content'])) {
                return array(
                    'success' => false,
                    'alt_text' => '',
                    'message' => 'Invalid response from OpenAI API'
                );
            }

            $alt_text = trim($data['choices'][0]['message']['content']);
            
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
        return apply_filters( 'aatg_supported_models', array(
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o Mini',
        ), 'openai' );
    }

    /**
     * Get default model
     *
     * @return string
     */
    public function get_default_model() {
        /**
         * Filter the default model for a provider. Lets sites (or a future plugin
         * update) swap the model without code changes if one is deprecated.
         *
         * gpt-4o (not -mini) is the default: OpenAI's mini tier applies a much
         * higher image-token multiplier, so on image-only workloads gpt-4o-mini
         * costs MORE per image than gpt-4o while producing weaker alt text.
         *
         * @since 2.5.1
         * @param string $model
         * @param string $provider
         */
        return apply_filters( 'aatg_default_model', 'gpt-4o', 'openai' );
    }

    /**
     * Vision-capable chat models this plugin will offer from the live catalog.
     *
     * OpenAI's /v1/models returns the entire account catalog — embeddings,
     * TTS, whisper, moderation, plus near-miss names like gpt-4o-mini-tts.
     * An unfiltered dropdown would let someone pick text-embedding-3-small
     * and break every generation, so we allowlist by exact alias.
     *
     * @since 2.6.5
     * @return array of model aliases
     */
    protected function get_vision_model_allowlist() {
        /**
         * Filter the OpenAI vision-model allowlist used for the live model
         * dropdown. Add ids here if you use a model family we don't list.
         *
         * @since 2.6.5
         * @param array $allowlist Exact model aliases (dated suffixes stripped).
         */
        return apply_filters( 'aatg_openai_vision_models', array(
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-4.1',
            'gpt-4.1-mini',
            'gpt-4-turbo',
            'chatgpt-4o-latest',
            'gpt-5',
            'gpt-5-mini',
        ) );
    }

    /**
     * Fetch the live model catalog from OpenAI, filtered to vision-capable
     * chat models. Dated snapshots are collapsed to their stable alias.
     *
     * @since 2.6.5
     * @param string $api_key
     * @return array id => display label
     */
    protected function fetch_model_catalog($api_key) {
        $response = $this->make_request(
            'https://api.openai.com/v1/models',
            array('Authorization' => 'Bearer ' . $api_key),
            '',
            'GET'
        );

        $result = $this->handle_response($response, 'OpenAI');
        if (!$result['success'] || empty($result['data']['data']) || !is_array($result['data']['data'])) {
            return array();
        }

        $allowlist = $this->get_vision_model_allowlist();
        $catalog = array();
        foreach ($result['data']['data'] as $entry) {
            if (empty($entry['id'])) {
                continue;
            }
            $alias = $this->to_alias($entry['id']);
            if (!in_array($alias, $allowlist, true) || isset($catalog[$alias])) {
                continue;
            }
            $catalog[$alias] = $alias;
        }

        return $catalog;
    }
    
    /**
     * Get API key help URL
     * 
     * @return string
     */
    public function get_api_key_help_url() {
        return 'https://help.openai.com/en/articles/4936850-where-do-i-find-my-openai-api-key';
    }
} 