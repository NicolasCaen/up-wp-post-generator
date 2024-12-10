<?php
class Markdown_Processor extends Abstract_Content_Processor {
    public static function uses_chatgpt() {
        return false;
    }

    public function process($content) {
        $blocks = parse_blocks($content);
        return $this->convert_to_markdown($blocks);
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
            // Gestion récursive des blocs imbriqués
            if (isset($block['innerBlocks']) && !empty($block['innerBlocks'])) {
                $markdown .= $this->convert_to_markdown($block['innerBlocks']);
            }

            if (isset($block['innerHTML'])) {
                if (isset($block['blockName']) && $block['blockName'] === 'core/quote') {
                    if (preg_match('/<p>(.*?)<\/p>/s', $block['innerHTML'], $matches)) {
                        $content = trim($matches[1]);
                        $markdown .= '> ' . $content . "\n\n";
                    }
                }
                elseif (preg_match('/>([^>]+)<\//', $block['innerHTML'], $matches)) {
                    $content = $matches[1];
                    
                    if (strpos($block['innerHTML'], '<h2') !== false) {
                        $markdown .= '## ' . $content . "\n\n";
                    }
                    elseif (strpos($block['innerHTML'], '<h3') !== false) {
                        $markdown .= '### ' . $content . "\n\n";
                    }
                    elseif (strpos($block['innerHTML'], '<h4') !== false) {
                        $markdown .= '#### ' . $content . "\n\n";
                    }
                    elseif (strpos($block['innerHTML'], '<p') !== false) {
                        $markdown .= $content . "\n\n";
                    }
                    elseif (strpos($block['innerHTML'], '<li') !== false) {
                        $markdown .= '- ' . $content . "\n";
                    }
                }
            }
        }
        error_log("markdown 1".$markdown);
        return $markdown;
    }
} 