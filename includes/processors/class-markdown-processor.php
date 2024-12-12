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

    private function convert_to_markdown($blocks) {
        if (!is_array($blocks) || empty($blocks)) {
            return '';
        }
        
        $markdown = '';
        foreach ($blocks as $block) {
            // Log pour debug
            error_log("Type de bloc: " . print_r($block['blockName'], true));
            error_log("Contenu HTML: " . print_r($block['innerHTML'], true));
            
            switch($block['blockName']) {
                case 'core/heading':
                    if (preg_match('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/s', $block['innerHTML'], $matches)) {
                        $level = $matches[1];
                        $content = trim($matches[2]);
                        $markdown .= str_repeat('#', intval($level)) . ' ' . $content . "\n\n";
                    }
                    break;
                    
                    
                case 'core/paragraph':
                    if (preg_match('/<p>(.*?)<\/p>/s', $block['innerHTML'], $matches)) {
                        $content = trim($matches[1]);
                        $markdown .= $content . "\n\n";
                        error_log("Paragraphe détecté: " . $content);
                    }
                    break;
                    
                case 'core/list':
                    if (isset($block['innerBlocks'])) {
                        foreach ($block['innerBlocks'] as $item) {
                            if ($item['blockName'] === 'core/list-item') {
                                if (preg_match('/<li[^>]*>(.*?)<\/li>/s', $item['innerHTML'], $matches)) {
                                    $content = trim($matches[1]);
                                    $markdown .= '- ' . $content . "\n";
                                }
                            }
                        }
                        $markdown .= "\n";
                    }
                    break;
            }

            if (isset($block['innerBlocks']) && !empty($block['innerBlocks'])) {
                $inner_markdown = $this->convert_to_markdown($block['innerBlocks']);
                $markdown .= $inner_markdown;
            }
        }
        
        return $markdown;
    }
} 