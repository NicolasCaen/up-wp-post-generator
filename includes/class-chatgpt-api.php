<?php
class ChatGPT_API {
    private $api_key;
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    public function __construct() {
        $this->api_key = get_option('chatgpt_api_key');
    }

    public function generate_content($parsed_content, $prompt) {
        $messages = array(
            array(
                'role' => 'system',
                'content' => 'Vous êtes un assistant qui génère du contenu WordPress en conservant la structure existante.'
            ),
            array(
                'role' => 'user',
                'content' => $this->format_prompt($parsed_content, $prompt)
            )
        );

        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'gpt-4',
                'messages' => $messages,
                'temperature' => 0.7
            ))
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return array(
            'success' => true,
            'content' => $this->parse_response($body['choices'][0]['message']['content'])
        );
    }

    private function format_prompt($parsed_content, $user_prompt) {
        return sprintf(
            "Structure actuelle du contenu:\n\n%s\n\nInstruction: %s\n\nGénérez un nouveau contenu en conservant exactement la même structure.",
            json_encode($parsed_content, JSON_PRETTY_PRINT),
            $user_prompt
        );
    }

    private function parse_response($response) {
        // Convertit la réponse markdown en blocs Gutenberg
        return $response;
    }
}