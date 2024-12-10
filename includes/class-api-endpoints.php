<?php
class ChatGPT_API_Endpoints {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('chatgpt-content-generator/v1', '/generate', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_content'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'prompt' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'instruction_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => Processor_Registry::get_available_types(),
                ),
                'follow_up_prompt' => array(
                    'required' => false,
                    'type' => 'string',
                ),
            ),
        ));
        register_rest_route('chatgpt-content-generator/v1', '/utility', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_utility'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'utility_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => Utility_Registry::get_available_types(),
                ),
            ),
        ));
    }

    public function check_permissions($request) {
        if (!current_user_can('edit_posts')) {
            return new WP_Error(
                'rest_forbidden',
                'Désolé, vous n\'avez pas les permissions nécessaires.',
                array('status' => 403)
            );
        }

        // Vérification du nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'rest_cookie_invalid_nonce',
                'Nonce invalide.',
                array('status' => 403)
            );
        }

        return true;
    }
    public function process_utility($request) {
        try {
            $params = $request->get_params();
            $utility = Utility_Registry::get_utility($params['utility_type']);
            $result = $utility->process($params['content']);
    
            return new WP_REST_Response([
                'success' => true,
                'content' => $result
            ], 200);
    
        } catch (Exception $e) {
            return new WP_Error(
                'utility_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    public function generate_content($request) {
        try {
            $params = $request->get_params();
            
            $processor = Processor_Registry::get_processor($params['instruction_type']);
            $result = $processor->process(
                $params['content'],
                $params['prompt'],
                $params['follow_up_prompt'] ?? ''
            );

            return new WP_REST_Response([
                'success' => true,
                'content' => $result
            ], 200);

        } catch (Exception $e) {
            return new WP_Error(
                'generation_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }
} 