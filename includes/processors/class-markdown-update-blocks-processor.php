<?php
class Markdown_Update_Blocks_Processor extends Abstract_Content_Processor {
    private $markdown_converter;
    private $gutenberg_converter;

    public function __construct() {
        $this->markdown_converter = new Markdown_Converter();
        $this->gutenberg_converter = new Markdown_To_Gutenberg_Converter();
    }

    public static function get_type() {
        return 'markdown_update_blocks';
    }

    public static function get_label() {
        return __('Mettre à jour avec Markdown', 'chatgpt-content-generator');
    }

    public function process($params) {
        error_log('Début process markdown_update_blocks');
        error_log('Paramètres reçus : ' . print_r($params, true));

        if (!is_array($params)) {
            error_log('Les paramètres ne sont pas un tableau');
            throw new Exception('Format de paramètres invalide');
        }

        if (empty($params['content'])) {
            error_log('Contenu markdown manquant');
            throw new Exception('Contenu markdown requis');
        }

        if (empty($params['original_blocks'])) {
            error_log('Blocs originaux manquants');
            throw new Exception('Blocs originaux requis');
        }

        try {
            $markdown_content = $params['content'];
            error_log('Markdown content: ' . $markdown_content);

            $original_blocks = parse_blocks($params['original_blocks']);
            error_log('Original blocks parsed: ' . print_r($original_blocks, true));
            
            // Convertir le markdown modifié en blocs Gutenberg
            $new_blocks = parse_blocks($this->gutenberg_converter->process($markdown_content));
            error_log('New blocks generated: ' . print_r($new_blocks, true));
            
            // Mettre à jour les blocs existants avec le nouveau contenu
            $updated_blocks = $this->merge_blocks($original_blocks, $new_blocks);
            error_log('Updated blocks: ' . print_r($updated_blocks, true));
            
            $result = serialize_blocks($updated_blocks);
            error_log('Final serialized result: ' . $result);
            
            return $result;
        } catch (Exception $e) {
            error_log('Erreur dans markdown_update_blocks : ' . $e->getMessage());
            error_log('Stack trace : ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function merge_blocks($original_blocks, $new_blocks) {
        $updated_blocks = [];
        $new_block_index = 0;
        
        foreach ($original_blocks as $original_block) {
            $updated_block = $this->update_block_content(
                $original_block, 
                $new_blocks, 
                $new_block_index
            );
            
            if ($updated_block) {
                $updated_blocks[] = $updated_block;
                $new_block_index++;
            }
        }

        // Ajouter les nouveaux blocs qui n'ont pas été utilisés
        while ($new_block_index < count($new_blocks)) {
            $updated_blocks[] = $new_blocks[$new_block_index];
            $new_block_index++;
        }

        return $updated_blocks;
    }

    private function update_block_content($original_block, $new_blocks, &$new_block_index) {
        if (!isset($new_blocks[$new_block_index])) {
            return null;
        }

        $new_block = $new_blocks[$new_block_index];

        switch ($original_block['blockName']) {
            case 'core/paragraph':
            case 'core/heading':
                $original_block['innerHTML'] = $new_block['innerHTML'];
                $original_block['innerContent'] = $new_block['innerContent'];
                return $original_block;

            case 'core/image':
                return $this->update_image_block($original_block, $new_block);

            case 'core/list':
                return $this->update_list_block($original_block, $new_block);

            case 'core/media-text':
                return $this->update_media_text_block($original_block, $new_block);

            case 'core/columns':
                $updated_inner_blocks = [];
                foreach ($original_block['innerBlocks'] as $inner_block) {
                    $updated_inner = $this->update_block_content(
                        $inner_block,
                        $new_blocks[$new_block_index]['innerBlocks'] ?? [],
                        $new_block_index
                    );
                    if ($updated_inner) {
                        $updated_inner_blocks[] = $updated_inner;
                    }
                }
                $original_block['innerBlocks'] = $updated_inner_blocks;
                return $original_block;

            default:
                return $original_block;
        }
    }

    private function update_image_block($original_block, $new_block) {
        // Vérifier si l'image a changé
        $original_url = $this->extract_image_url($original_block);
        $new_url = $this->extract_image_url($new_block);

        if ($original_url !== $new_url) {
            return $new_block;
        }

        return $original_block;
    }

    private function update_list_block($original_block, $new_block) {
        // Mettre à jour les éléments de la liste
        $original_block['innerBlocks'] = $new_block['innerBlocks'];
        $original_block['innerHTML'] = $new_block['innerHTML'];
        $original_block['innerContent'] = $new_block['innerContent'];
        return $original_block;
    }

    private function update_media_text_block($original_block, $new_block) {
        // Mettre à jour l'image si nécessaire
        $original_media = $this->extract_image_url($original_block);
        $new_media = $this->extract_image_url($new_block);

        if ($original_media !== $new_media) {
            $original_block['attrs']['mediaUrl'] = $new_media;
        }

        // Mettre à jour le contenu texte
        if (!empty($original_block['innerBlocks'])) {
            foreach ($original_block['innerBlocks'] as $index => $inner_block) {
                if (isset($new_block['innerBlocks'][$index])) {
                    $original_block['innerBlocks'][$index] = $this->update_block_content(
                        $inner_block,
                        [$new_block['innerBlocks'][$index]],
                        $dummy_index = 0
                    );
                }
            }
        }

        return $original_block;
    }

    private function extract_image_url($block) {
        if (isset($block['attrs']['url'])) {
            return $block['attrs']['url'];
        }

        // Extraire l'URL de l'image depuis innerHTML si nécessaire
        if (preg_match('/src="([^"]+)"/', $block['innerHTML'], $matches)) {
            return $matches[1];
        }

        return '';
    }
} 