<?php
abstract class Abstract_ChatGPT_Processor extends Abstract_Content_Processor {
    protected $api_endpoint = 'https://api.openai.com/v1/chat/completions';
    protected $timeout = 60;

    public static function uses_chatgpt() {
        return true;
    }

    protected function call_chatgpt($messages) {
        $api_key = get_option('chatgpt_api_key');
        if (empty($api_key)) {
            throw new Exception(__('La clé API ChatGPT n\'est pas configurée.', 'chatgpt-content-generator'));
        }

        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'gpt-4',
                'messages' => $messages,
                'temperature' => 0.7
            )),
            'timeout' => $this->timeout
        ));

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['choices'][0]['message']['content'])) {
            throw new Exception(__('Réponse invalide de l\'API ChatGPT', 'chatgpt-content-generator'));
        }

        return $this->sanitize_content($body['choices'][0]['message']['content']);
    }

    protected function prepare_messages($content, $prompt, $follow_up_prompt) {
        $messages = array(
            array(
                'role' => 'system',
                'content' => $this->get_system_message()
            )
        );

        // Ajouter le contexte s'il existe
        $context = json_decode(get_option('chatgpt_context', '[]'), true);
        if (!empty($context) && is_array($context)) {
            foreach ($context as $ctx_message) {
                if (isset($ctx_message['role']) && isset($ctx_message['content'])) {
                    $messages[] = array(
                        'role' => sanitize_text_field($ctx_message['role']),
                        'content' => sanitize_textarea_field($ctx_message['content'])
                    );
                }
            }
        }

        $messages[] = array(
            'role' => 'user',
            'content' => $this->format_prompt($content, $prompt)
        );

        if (!empty($follow_up_prompt)) {
            $messages[] = array(
                'role' => 'user',
                'content' => $follow_up_prompt
            );
        }

        return $messages;
    }

    abstract protected function get_system_message();
    abstract protected function format_prompt($content, $prompt);

    public function process($content, $prompt = '', $follow_up_prompt = '') {
        $messages = $this->prepare_messages($content, $prompt, $follow_up_prompt);
        return $this->call_chatgpt($messages);
    }
} 