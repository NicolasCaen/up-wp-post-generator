<?php
class Markdown_Converter extends Abstract_Utility {
    public static function get_type() {
        return 'markdown';
    }

    public static function get_label() {
        return __('Conversion Markdown', 'chatgpt-content-generator');
    }

    public function process($content) {
        if (is_array($content)) {
            error_log('Content reçu comme tableau: ' . print_r($content, true));
            $content = isset($content['content']) ? $content['content'] : '';
        }

        if (!is_string($content)) {
            error_log('Type de contenu invalide: ' . gettype($content));
            throw new Exception('Le contenu doit être une chaîne de caractères');
        }

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
            $markdown .= $this->convert_block_to_markdown($block);
        }
        
        return trim($markdown);
    }

    private function convert_block_to_markdown($block) {
        // Log pour déboguer la structure des blocs
        error_log('Block reçu: ' . print_r($block, true));

        if (!isset($block['blockName'])) {
            return '';
        }

        $markdown = '';

        switch ($block['blockName']) {
            case 'core/heading':
                $level = isset($block['attrs']['level']) ? $block['attrs']['level'] : 2;
                $content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
                $content = stripslashes($content);
                $markdown .= str_repeat('#', $level) . ' ' . trim($content) . "\n\n";
                break;
                
            case 'core/paragraph':
                $content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
                $content = stripslashes($content);
                $markdown .= trim($content) . "\n\n";
                break;
                
            case 'core/list':
                $isOrdered = isset($block['attrs']['ordered']) && $block['attrs']['ordered'];
                if (isset($block['innerBlocks'])) {
                    foreach ($block['innerBlocks'] as $index => $item) {
                        if ($item['blockName'] === 'core/list-item') {
                            $content = isset($item['innerHTML']) ? strip_tags($item['innerHTML']) : '';
                            $content = stripslashes($content);
                            $prefix = $isOrdered ? ($index + 1) . '. ' : '- ';
                            $markdown .= $prefix . trim($content) . "\n";
                            
                            // Gestion des sous-listes
                            if (!empty($item['innerBlocks'])) {
                                foreach ($item['innerBlocks'] as $innerBlock) {
                                    if ($innerBlock['blockName'] === 'core/list') {
                                        $innerContent = $this->convert_block_to_markdown($innerBlock);
                                        // Indenter les sous-listes
                                        $markdown .= preg_replace('/^/m', '    ', $innerContent);
                                    }
                                }
                            }
                        }
                    }
                    $markdown .= "\n";
                }
                break;

            case 'core/list-item':
                $content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
                $content = stripslashes($content);
                return trim($content);
                break;

            case 'core/image':
                // Extraire l'URL de l'image depuis innerHTML en utilisant DOMDocument
                if (isset($block['innerHTML'])) {
                    $dom = new DOMDocument();
                    @$dom->loadHTML(mb_convert_encoding($block['innerHTML'], 'HTML-ENTITIES', 'UTF-8'));
                    $images = $dom->getElementsByTagName('img');
                    
                    if ($images->length > 0) {
                        $img = $images->item(0);
                        $url = $img->getAttribute('src');
                        $alt = $img->getAttribute('alt');
                        
                        $markdown .= "![" . $alt . "](" . $url . ")\n\n";
                    }
                }
                break;

            default:
                // Gestion récursive des blocs imbriqués
                if (isset($block['innerBlocks']) && !empty($block['innerBlocks'])) {
                    foreach ($block['innerBlocks'] as $innerBlock) {
                        $markdown .= $this->convert_block_to_markdown($innerBlock);
                    }
                }
                break;
        }

        return $markdown;
    }
} 