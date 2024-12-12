<?php

class Markdown_To_Gutenberg_Converter extends Abstract_Utility {
    public static function get_type() {
        return 'markdown_to_gutenberg';
    }

    public static function get_label() {
        return __('Conversion Markdown vers Gutenberg', 'chatgpt-content-generator');
    }

    public function process($markdown) {
        if (!is_string($markdown)) {
            error_log('Type de contenu invalide: ' . gettype($markdown));
            throw new Exception('Le contenu doit être une chaîne de caractères');
        }

        if (empty($markdown)) {
            return '';
        }

        $lines = explode("\n", $markdown);
        $gutenberg = '';
        $listBuffer = '';
        $inList = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                if ($inList) {
                    $gutenberg .= $this->closeList($listBuffer);
                    $listBuffer = '';
                    $inList = false;
                }
                continue;
            }

            // Listes
            if (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                if (!$inList) {
                    $inList = true;
                    $listBuffer = "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n";
                }
                $listBuffer .= $this->create_list_item($matches[1]);
                continue;
            }

            // Si on n'est plus dans une liste
            if ($inList && !preg_match('/^[-*]\s+(.+)$/', $line)) {
                $gutenberg .= $this->closeList($listBuffer);
                $listBuffer = '';
                $inList = false;
            }

            // Autres types de contenu
            if (!$inList) {
                if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
                    $gutenberg .= $this->create_heading_block($matches[2], strlen($matches[1]));
                } elseif (preg_match('/^!\[(.*?)\]\((.*?)\)$/', $line, $matches)) {
                    $gutenberg .= $this->create_image_block($matches[2], $matches[1]);
                } elseif (preg_match('/^>\s+(.+)$/', $line, $matches)) {
                    $gutenberg .= $this->create_quote_block($matches[1]);
                } else {
                    $gutenberg .= $this->create_paragraph($line);
                }
            }
        }

        // Fermer la dernière liste si nécessaire
        if ($inList) {
            $gutenberg .= $this->closeList($listBuffer);
        }

        return $gutenberg;
    }

    private function create_list_item($content) {
        return sprintf(
            "<!-- wp:list-item -->\n<li>%s</li>\n<!-- /wp:list-item -->\n",
            esc_html($content)
        );
    }

    private function closeList($listBuffer) {
        return $listBuffer . "</ul>\n<!-- /wp:list -->\n\n";
    }

    private function create_heading_block($content, $level) {
        return sprintf(
            "<!-- wp:heading {\"level\":%d} -->\n<h%d class=\"wp-block-heading\">%s</h%d>\n<!-- /wp:heading -->\n\n",
            $level,
            $level,
            esc_html($content),
            $level
        );
    }

    private function create_paragraph($content) {
        return sprintf(
            "<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->\n\n",
            esc_html($content)
        );
    }

    private function create_image_block($url, $alt) {
        return sprintf(
            "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"%s\" alt=\"%s\"/></figure>\n<!-- /wp:image -->\n\n",
            esc_url($url),
            esc_attr($alt)
        );
    }

    private function create_quote_block($content) {
        return sprintf(
            "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\">\n%s\n</blockquote>\n<!-- /wp:quote -->\n\n",
            esc_html($content)
        );
    }
} 