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

    private function convert_to_markdown($block) {
   
        $markdown_converter = new Markdown_Converter();
        error_log(json_encode($markdown_converter));
        return $markdown_converter->process($block);
    }
} 