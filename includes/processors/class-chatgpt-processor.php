<?php

class ChatGPT_Processor extends Abstract_ChatGPT_Processor {
    public static function get_type() {
        return 'new_content';
    }

    public static function get_label() {
        return __('Nouveau contenu', 'chatgpt-content-generator');
    }

    protected function get_system_message() {
        return __(
            'Vous êtes un assistant spécialisé dans la génération de contenu WordPress. ' .
            'Générez du contenu en respectant le format Gutenberg et en maintenant un style professionnel.',
            'chatgpt-content-generator'
        );
    }

    protected function format_prompt($content, $prompt) {
        return sprintf(
            __("Contenu actuel:\n\n%s\n\nInstructions: %s", 'chatgpt-content-generator'),
            $content,
            $prompt
        );
    }
} 