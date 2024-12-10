<?php
class SEO_Processor extends Abstract_ChatGPT_Processor {
    public static function get_type() {
        return 'seo';
    }

    public static function get_label() {
        return __('Optimisation SEO', 'chatgpt-content-generator');
    }

    protected function get_system_message() {
        return __(
            'Vous êtes un expert SEO. Générez des métadonnées optimisées pour le référencement ' .
            'en respectant les bonnes pratiques SEO actuelles.',
            'chatgpt-content-generator'
        );
    }

    protected function format_prompt($content, $prompt) {
        return sprintf(
            __("Contenu de la page:\n\n%s\n\nType de métadonnée à générer: %s", 'chatgpt-content-generator'),
            $content,
            $prompt
        );
    }

    public function process($content, $prompt = '', $follow_up_prompt = '') {
        $result = parent::process($content, $prompt, $follow_up_prompt);
        
        // Récupérer l'ID du post courant
        $post_id = get_the_ID();
        
        if (!$post_id) {
            throw new Exception(__('ID du post non trouvé', 'chatgpt-content-generator'));
        }

        // Mettre à jour les métadonnées SEO
        switch ($prompt) {
            case 'title':
                $this->update_seo_meta($post_id, 'seo_title', $result);
                break;
            case 'description':
                $this->update_seo_meta($post_id, 'seo_description', $result);
                break;
        }

        return $result;
    }

    private function update_seo_meta($post_id, $type, $content) {
        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            switch ($type) {
                case 'seo_title':
                    update_post_meta($post_id, '_yoast_wpseo_title', $content);
                    break;
                case 'seo_description':
                    update_post_meta($post_id, '_yoast_wpseo_metadesc', $content);
                    break;
            }
        }
        
        // Rank Math
        if (class_exists('RankMath')) {
            switch ($type) {
                case 'seo_title':
                    update_post_meta($post_id, 'rank_math_title', $content);
                    break;
                case 'seo_description':
                    update_post_meta($post_id, 'rank_math_description', $content);
                    break;
            }
        }

        return true;
    }

    protected function sanitize_content($content, $prompt = '') {
        // Supprimer les balises HTML et les caractères spéciaux
        $content = wp_strip_all_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        
        // Limiter la longueur en fonction du type
        if (strpos($prompt, 'title') !== false) {
            return substr($content, 0, 60); // Limite pour les titres SEO
        } else {
            return substr($content, 0, 155); // Limite pour les descriptions
        }
    }
} 