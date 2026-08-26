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
            $model = $this->resolve_model();

            $params = array_merge(
                array(
                    'model' => $model,
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
                ),
                self::request_params_for_model($model)
            );

            $result = $this->request_completion($params, $api_key);

            if (!$result['success']) {
                return array(
                    'success' => false,
                    'alt_text' => '',
                    'message' => $result['message']
                );
            }

            $data = $result['data'];
            $alt_text = isset($data['choices'][0]['message']['content'])
                ? trim((string) $data['choices'][0]['message']['content'])
                : '';

            if ('' === $alt_text) {
                return array(
                    'success' => false,
                    'alt_text' => '',
                    'message' => $this->empty_completion_message($data, $model)
                );
            }

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
     * Whether a model expects OpenAI's reasoning-model request shape.
     *
     * GPT-5 and the o-series reject `max_tokens` outright — "Unsupported
     * parameter: 'max_tokens' is not supported with this model. Use
     * 'max_completion_tokens' instead." — and accept only the default
     * temperature. They also spend part of the completion budget on hidden
     * reasoning tokens, so the cap has to be far larger than the ~100 tokens
     * of visible text an alt text actually needs.
     *
     * @since 2.6.8
     * @param string $model
     * @return bool
     */
    public static function is_reasoning_model($model) {
        return (bool) preg_match('/^(gpt-5|o[1-9])/i', (string) $model);
    }

    /**
     * Token-limit and sampling parameters for a chat completion, per model family.
     *
     * @since 2.6.8
     * @param string $model
     * @return array Parameters to merge into the request body.
     */
    public static function request_params_for_model($model) {
        if (self::is_reasoning_model($model)) {
            /**
             * Filter the completion budget sent to an OpenAI reasoning model.
             *
             * This covers reasoning tokens as well as visible output, which is
             * why it is not the ~100 tokens an alt text occupies. Unused budget
             * is not billed, so err high rather than truncating the answer.
             *
             * @since 2.6.8
             * @param int    $tokens
             * @param string $model
             */
            $tokens = apply_filters('aatg_openai_max_completion_tokens', 2000, $model);

            /**
             * Filter the reasoning effort for OpenAI reasoning models.
             *
             * 'low' is the value every GPT-5 generation accepts; describing an
             * image needs no deliberation, and lower effort means fewer billed
             * reasoning tokens. Set to null to omit the parameter entirely.
             *
             * @since 2.6.8
             * @param string|null $effort
             * @param string      $model
             */
            $effort = apply_filters('aatg_openai_reasoning_effort', 'low', $model);

            $params = array('max_completion_tokens' => (int) $tokens);
            if (!empty($effort)) {
                $params['reasoning_effort'] = $effort;
            }

            return $params;
        }

        return array(
            'max_tokens'  => 100,
            'temperature' => 0.6,
        );
    }

    /**
     * POST a chat completion, retrying without any parameter the API rejects.
     *
     * request_params_for_model() already sends the right shape for the model
     * families we know about. The retry is for the ones we don't: a future
     * model that drops `temperature` or renames another field degrades to a
     * working request instead of failing the whole run. Bounded at two retries
     * and limited to optional parameters, so it can never loop or strip the
     * messages out from under the request.
     *
     * @since 2.6.8
     * @param array  $params
     * @param string $api_key
     * @return array handle_response() result
     */
    protected function request_completion($params, $api_key) {
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        );

        $result = array();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $response = $this->make_request(
                'https://api.openai.com/v1/chat/completions',
                $headers,
                wp_json_encode($params)
            );

            $result = $this->handle_response($response, 'OpenAI');

            if ($result['success']) {
                return $result;
            }

            $retry = $this->drop_rejected_param($params, $result);
            if (null === $retry) {
                return $result;
            }

            $params = $retry;
        }

        return $result;
    }

    /**
     * Rewrite a request body to drop the parameter OpenAI just rejected.
     *
     * Returns null when there is nothing safe to retry, which is the common
     * case — an auth failure or a bad image must surface as-is.
     *
     * @since 2.6.8
     * @param array $params
     * @param array $result handle_response() result for the failed attempt
     * @return array|null
     */
    protected function drop_rejected_param($params, $result) {
        if (400 !== (int) (isset($result['response_code']) ? $result['response_code'] : 0)) {
            return null;
        }

        $error   = isset($result['data']['error']) && is_array($result['data']['error']) ? $result['data']['error'] : array();
        $param   = isset($error['param']) ? (string) $error['param'] : '';
        $code    = isset($error['code']) ? (string) $error['code'] : '';
        $message = isset($error['message']) ? (string) $error['message'] : '';

        $optional = array('max_tokens', 'max_completion_tokens', 'temperature', 'reasoning_effort');

        if ('' === $param || !isset($params[$param]) || !in_array($param, $optional, true)) {
            return null;
        }

        $unsupported = in_array($code, array('unsupported_parameter', 'unsupported_value'), true)
            || preg_match('/unsupported (parameter|value)/i', $message);

        if (!$unsupported) {
            return null;
        }

        $dropped = $params[$param];
        unset($params[$param]);

        // A rename, not a removal: keep the budget under the name it asked for.
        if (preg_match('/\buse \'?(max_completion_tokens|max_tokens)\'?/i', $message, $m)
            && !isset($params[$m[1]])) {
            $params[$m[1]] = $dropped;
        }

        return $params;
    }

    /**
     * Explain a completion that came back with no text.
     *
     * Reasoning models can spend the entire budget on reasoning tokens and
     * return an empty message with finish_reason "length" — a 200 response
     * that "Invalid response from OpenAI API" describes badly.
     *
     * @since 2.6.8
     * @param array  $data Decoded response body
     * @param string $model
     * @return string
     */
    protected function empty_completion_message($data, $model) {
        $finish = isset($data['choices'][0]['finish_reason']) ? $data['choices'][0]['finish_reason'] : '';

        if ('length' === $finish && self::is_reasoning_model($model)) {
            return sprintf(
                'The model %s used its whole token budget before writing any alt text. Raise it with the aatg_openai_max_completion_tokens filter, or choose a non-reasoning model such as gpt-4o.',
                $model
            );
        }

        if ('content_filter' === $finish) {
            return 'OpenAI declined to describe this image.';
        }

        return 'Invalid response from OpenAI API';
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