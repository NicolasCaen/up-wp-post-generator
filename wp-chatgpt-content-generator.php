<?php
/**
 * Plugin Name: ChatGPT Content Generator
 * Description: Génère du contenu pour Gutenberg en utilisant ChatGPT
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-chatgpt-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-content-parser.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';

class WP_ChatGPT_Content_Generator {
    private $chatgpt_api;
    private $content_parser;
    private $admin_interface;

    public function __construct() {
        $this->chatgpt_api = new ChatGPT_API();
        $this->content_parser = new Content_Parser();
        $this->admin_interface = new Admin_Interface();

        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function enqueue_assets($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'chatgpt-content-generator',
            plugin_dir_url(__FILE__) . 'assets/js/content-generator.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor'),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'chatgpt-content-generator',
            plugin_dir_url(__FILE__) . 'assets/css/content-generator.css',
            array(),
            '1.0.0'
        );
    }

    public function register_rest_routes() {
        register_rest_route('chatgpt-content-generator/v1', '/generate', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_content'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            }
        ));
    }

    public function generate_content($request) {
        $params = $request->get_params();
        $content = $params['content'];
        $prompt = $params['prompt'];

        $parsed_content = $this->content_parser->parse_blocks($content);
        $chatgpt_response = $this->chatgpt_api->generate_content($parsed_content, $prompt);

        return rest_ensure_response($chatgpt_response);
    }
}

new WP_ChatGPT_Content_Generator();