<?php
class Markdown_Processor extends Abstract_Content_Processor {
    private $markdown_converter;

    public function __construct() {
        $this->markdown_converter = new Markdown_Converter();
    }

    public static function uses_chatgpt() {
        return false;
    }

    public function process($content) {
        $content = str_replace(['<br>', '</br>', '<br/>', '<br />'], "\n", $content);
        $content = str_replace(["'"], "\'", $content);
        try {
            return $this->markdown_converter->process($content);
        } catch (Exception $e) {
            error_log("Erreur lors de la conversion Markdown : " . $e->getMessage());
            return '';
        }
    }

    public static function get_type() {
        return 'generate_markdown';
    }

    public static function get_label() {
        return __('Générer Markdown', 'chatgpt-content-generator');
    }
} 