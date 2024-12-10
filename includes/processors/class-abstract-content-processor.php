<?php
abstract class Abstract_Content_Processor {
    abstract public function process($content);
    abstract public static function get_type();
    abstract public static function get_label();
    
    // Nouvelle méthode pour définir si le processeur utilise ChatGPT
    public static function uses_chatgpt() {
        return false; // Par défaut, les processeurs n'utilisent pas ChatGPT
    }

    // Méthode utilitaire commune à tous les processeurs
    protected function sanitize_content($content) {
        return wp_kses_post($content);
    }
} 