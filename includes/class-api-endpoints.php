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
                'type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => Processor_Registry::get_available_types(),
                ),
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'prompt' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'follow_up_prompt' => array(
                    'required' => false,
                    'type' => 'string',
                ),
            ),
        ));
        register_rest_route('chatgpt-content-generator/test', '/page-titles/', array(
            'methods' => 'GET',
            'permission_callback' => array($this, 'check_permissions'),
            'callback' => array($this, 'get_page_titles'),
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
        // Nouvelle route de test
    
    }
    public function get_page_titles() {
        $pages = get_pages();
        $titles = array();
    
        foreach ($pages as $page) {
            $titles[] = $page->post_title;
        }
    
        return new WP_REST_Response($titles, 200);
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
            error_log('Params reçus: ' . print_r($params, true)); // Debug log
            $type = $params['type'];
            error_log('Type reçu: ' . $type); // Debug log
                
            // Vérifier si le processeur nécessite un prompt
            if (Processor_Registry::requires_prompt($type) && empty($params['prompt'])) {
                throw new Exception(__('Veuillez saisir les instructions', 'chatgpt-content-generator'));
            }
            
            $processor = Processor_Registry::get_processor($type);
            $result = $processor->process(
                $params['content'],
                $params['prompt'] ?? '',
                $params['follow_up_prompt'] ?? ''
            );
    
            return new WP_REST_Response([
                'success' => true,
                'content' => $result
            ], 200);
    
        } catch (Exception $e) {
            error_log('Erreur dans generate_content: ' . $e->getMessage()); // Debug log
            return new WP_Error(
                'generation_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }
} 