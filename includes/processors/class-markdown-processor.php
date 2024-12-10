<?php
class Markdown_Processor extends Abstract_Content_Processor {
    public static function uses_chatgpt() {
        return false;
    }

    public function process($content, $prompt = '', $follow_up_prompt = '') {
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
        $markdown = '';
        foreach ($blocks as $block) {
            $markdown .= $this->process_block($block);
        }
        return $markdown;
    }

    private function process_block($block) {
        // ... code de conversion en markdown ...
    }
} 