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

require_once plugin_dir_path(__FILE__) . 'includes/utils/class-utility-registry.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/class-abstract-utility.php';

require_once plugin_dir_path(__FILE__) . 'includes/class-processor-registry.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/class-markdown-converter.php';


require_once plugin_dir_path(__FILE__) . 'includes/processors/class-seo-processor.php';
require_once plugin_dir_path(__FILE__) . 'includes/processors/class-markdown-processor.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-api-endpoints.php';

add_action('init', function() {
    Processor_Registry::register_processor(SEO_Processor::class);
    Processor_Registry::register_processor(Markdown_Processor::class);

    new ChatGPT_API_Endpoints();
});

require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';



class UP_WP_Post_Generator {
    private $admin_interface;

    public function __construct() {
        $this->admin_interface = new Admin_Interface();

        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
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

        wp_enqueue_script(
            'codemirror-script',
            'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.js',
            array('wp-element'),
            '6.65.7',
            true
        );
        
        wp_enqueue_script(
            'codemirror-markdown',
            'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/markdown/markdown.min.js',
            array('codemirror-script'),
            '6.65.7',
            true
        );
        
        wp_enqueue_style(
            'codemirror-style',
            'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.css',
            array(),
            '6.65.7'
        );
        
        wp_enqueue_style(
            'codemirror-theme',
            'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/theme/monokai.min.css',
            array('codemirror-style'),
            '6.65.7'
        );

        wp_localize_script('chatgpt-content-generator', 'chatgptSettings', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'seoPluginActive' => defined('WPSEO_VERSION') || class_exists('RankMath'),
            'restUrl' => rest_url('chatgpt-content-generator/v1/'),
            'instructionOptions' => array(
                array(
                    'label' => 'Générer du Markdown',
                    'value' => 'generate_markdown',
                    'requiresPrompt' => false
                ),
                array(
                    'label' => 'Nouveau contenu',
                    'value' => 'new_content',
                    'requiresPrompt' => true
                ),
                array(
                    'label' => 'SEO',
                    'value' => 'seo',
                    'requiresPrompt' => true
                )
            )
        ));
    }

}

new UP_WP_Post_Generator();