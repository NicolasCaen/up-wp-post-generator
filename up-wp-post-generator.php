<?php
/**
 * Plugin Name: Up IA Content Generator
 * Description: Génère du contenu pour Gutenberg en utilisant l'IA
 * Version: 1.0.0
 * Author: GEHIN Nicolas
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/processors/class-abstract-content-processor.php';
require_once plugin_dir_path(__FILE__) . 'includes/processors/class-abstract-chatgpt-processor.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-api-endpoints.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-processor-registry.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/class-abstract-utility.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/class-markdown-converter.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/class-utility-registry.php';

// Charger tous les processeurs
foreach (glob(plugin_dir_path(__FILE__) . 'includes/processors/class-*-processor.php') as $processor_file) {
    if (basename($processor_file) !== 'class-abstract-content-processor.php' && 
        basename($processor_file) !== 'class-abstract-chatgpt-processor.php') {
        require_once $processor_file;
    }
}

// Enregistrer les processeurs disponibles
Processor_Registry::register_processor('Markdown_Processor');
Processor_Registry::register_processor('ChatGPT_Processor');
// Ajouter ici les autres processeurs au fur et à mesure

Utility_Registry::register_utility(Markdown_Converter::class);

require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-api-endpoints.php';


class UP_WP_Post_Generator {
    private $admin_interface;

    public function __construct() {
        $this->admin_interface = new Admin_Interface();

        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        new ChatGPT_API_Endpoints();
    }

    public function enqueue_assets($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'chatgpt-content-generator',
            plugin_dir_url(__FILE__) . 'assets/js/content-generator.js',
            array(
                'wp-blocks',
                'wp-element',
                'wp-components',
                'wp-editor',
                'wp-data',
                'wp-plugins',
                'wp-edit-post',
                'wp-i18n'
            ),
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
        wp_localize_script('chatgpt-content-generator', 'chatgptSettings', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'seoPluginActive' => defined('WPSEO_VERSION') || class_exists('RankMath'),
            'restUrl' => rest_url('chatgpt-content-generator/v1/')
        ));
    }

}

new UP_WP_Post_Generator();