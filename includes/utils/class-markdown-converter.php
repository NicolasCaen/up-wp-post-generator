<?php
class Markdown_Converter extends Abstract_Utility {
    public static function get_type() {
        return 'markdown';
    }

    public static function get_label() {
        return __('Conversion Markdown', 'chatgpt-content-generator');
    }

    public function process($content) {
        // S'assurer que $content est une chaîne de caractères
        if (is_array($content)) {
            error_log('Content reçu comme tableau: ' . print_r($content, true));
            $content = isset($content['content']) ? $content['content'] : '';
        }

        // Vérifier que le contenu est une chaîne valide
        if (!is_string($content)) {
            error_log('Type de contenu invalide: ' . gettype($content));
            throw new Exception('Le contenu doit être une chaîne de caractères');
        }

        // Parser les blocs seulement si le contenu n'est pas vide
        if (empty($content)) {
            return '';
        }

        $blocks = parse_blocks($content);
        return $this->blocks_to_markdown($blocks);
    }

    private function blocks_to_markdown($blocks) {
        if (!is_array($blocks)) {
            error_log('Blocks invalides reçus: ' . print_r($blocks, true));
            return '';
        }

        $markdown = '';
        
        foreach ($blocks as $block) {
            if (!isset($block['blockName'])) {
                continue;
            }

            switch ($block['blockName']) {
                case 'core/heading':
                    $level = isset($block['attrs']['level']) ? $block['attrs']['level'] : 2;
                    $content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
                    $markdown .= str_repeat('#', $level) . ' ' . trim($content) . "\n\n";
                    break;
                    
                case 'core/paragraph':
                    $content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
                    $markdown .= trim($content) . "\n\n";
                    break;
                    
                case 'core/list':
                    if (isset($block['innerBlocks'])) {
                        foreach ($block['innerBlocks'] as $item) {
                            $content = isset($item['innerHTML']) ? strip_tags($item['innerHTML']) : '';
                            $markdown .= "* " . trim($content) . "\n";
                        }
                    }
                    $markdown .= "\n";
                    break;
                    
                case 'core/quote':
                    $content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
                    $markdown .= "> " . trim($content) . "\n\n";
                    break;
            }
        }
        
        return trim($markdown);
    }
} 