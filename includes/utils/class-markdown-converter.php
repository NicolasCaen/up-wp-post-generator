<?php
class Markdown_Converter extends Abstract_Utility {
    public static function get_type() {
        return 'markdown';
    }

    public static function get_label() {
        return __('Conversion Markdown', 'chatgpt-content-generator');
    }

    public function process($content) {
        $parser = new Content_Parser();
        $blocks = $parser->parse_blocks($content);
        
        return $this->blocks_to_markdown($blocks);
    }

    private function blocks_to_markdown($blocks) {
        $markdown = '';
        
        foreach ($blocks as $block) {
            switch ($block['type']) {
                case 'core/heading':
                    $markdown .= str_repeat('#', $block['level']) . ' ' . $block['content'] . "\n\n";
                    break;
                    
                case 'core/paragraph':
                    $markdown .= $block['content'] . "\n\n";
                    break;
                    
                case 'core/list':
                    foreach ($block['items'] as $item) {
                        $markdown .= "* " . $item . "\n";
                    }
                    $markdown .= "\n";
                    break;
                    
                case 'core/quote':
                    $markdown .= "> " . $block['content'] . "\n\n";
                    break;
            }
        }
        
        return trim($markdown);
    }
} 