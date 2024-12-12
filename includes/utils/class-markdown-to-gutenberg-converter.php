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

        // Séparer le contenu en lignes
        $lines = explode("\n", $markdown);
        $gutenberg = '';
        $buffer = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Ignorer les lignes vides sauf si on veut forcer un saut de paragraphe
            if (empty($line) && empty($buffer)) {
                continue;
            }

            // Titres
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]);
                $content = $matches[2];
                $gutenberg .= $this->flush_buffer($buffer);
                $gutenberg .= $this->create_heading_block($content, $level);
                continue;
            }

            // Images
            if (preg_match('/^!\[(.*?)\]\((.*?)\)$/', $line, $matches)) {
                $alt = $matches[1];
                $url = $matches[2];
                $gutenberg .= $this->flush_buffer($buffer);
                $gutenberg .= $this->create_image_block($url, $alt);
                continue;
            }

            // Listes
            if (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                if (empty($buffer) || !str_contains($buffer, '<!-- wp:list -->')) {
                    $gutenberg .= $this->flush_buffer($buffer);
                    $buffer = "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n";
                }
                $buffer .= $this->create_list_item($matches[1]);
                continue;
            }

            // Listes numérotées
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                if (empty($buffer) || !str_contains($buffer, '<!-- wp:list {"ordered":true} -->')) {
                    $gutenberg .= $this->flush_buffer($buffer);
                    $buffer = "<!-- wp:list {\"ordered\":true} -->\n<ol class=\"wp-block-list\">\n";
                }
                $buffer .= $this->create_list_item($matches[1]);
                continue;
            }

            // Citations
            if (preg_match('/^>\s+(.+)$/', $line, $matches)) {
                if (empty($buffer) || !str_contains($buffer, '<!-- wp:quote -->')) {
                    $gutenberg .= $this->flush_buffer($buffer);
                    $buffer = "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\">\n";
                }
                $buffer .= $this->create_paragraph($matches[1]);
                continue;
            }

            // Paragraphes normaux
            if (!empty($line)) {
                if (empty($buffer) || str_contains($buffer, '</ul>') || str_contains($buffer, '</ol>') || str_contains($buffer, '</blockquote>')) {
                    $gutenberg .= $this->flush_buffer($buffer);
                    $buffer = '';
                }
                $buffer .= $this->create_paragraph($line);
            }
        }

        // Vider le buffer final
        $gutenberg .= $this->flush_buffer($buffer);

        return $gutenberg;
    }

    private function flush_buffer($buffer) {
        if (empty($buffer)) {
            return '';
        }

        // Fermer les balises ouvertes
        if (str_contains($buffer, '<ul class="wp-block-list">')) {
            $buffer .= "</ul>\n<!-- /wp:list -->\n";
        } elseif (str_contains($buffer, '<ol class="wp-block-list">')) {
            $buffer .= "</ol>\n<!-- /wp:list -->\n";
        } elseif (str_contains($buffer, '<blockquote class="wp-block-quote">')) {
            $buffer .= "</blockquote>\n<!-- /wp:quote -->\n";
        }

        return $buffer;
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

    private function create_list_item($content) {
        return sprintf(
            "<!-- wp:list-item -->\n<li>%s</li>\n<!-- /wp:list-item -->\n",
            esc_html($content)
        );
    }
} 