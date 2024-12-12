<?php
class Markdown_To_Blocks_Processor extends Abstract_Content_Processor {
    private $gutenberg_converter;

    public function __construct() {
        $this->gutenberg_converter = new Markdown_To_Gutenberg_Converter();
    }

    public static function uses_chatgpt() {
        return false;
    }

    public function process($content) {
        try {
            // Nettoyer les caractères d'échappement potentiels
            $content = stripslashes($content);
            
            // Décoder les entités HTML
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            // Convertir les retours à la ligne Windows en retours à la ligne Unix
            $content = str_replace("\r\n", "\n", $content);
            
            // Supprimer les balises HTML
            $content = strip_tags($content);
            
            // Convertir en blocs Gutenberg
            $blocks = $this->gutenberg_converter->process($content);
            
            return $blocks;
        } catch (Exception $e) {
            error_log("Erreur lors de la conversion Markdown vers Gutenberg : " . $e->getMessage());
            return '';
        }
    }

    public static function get_type() {
        return 'markdown_to_blocks';
    }

    public static function get_label() {
        return __('Convertir Markdown en Blocs', 'chatgpt-content-generator');
    }
} 