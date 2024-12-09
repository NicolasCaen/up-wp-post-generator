<?php
class Content_Parser {
    public function parse_blocks($content) {
        $blocks = parse_blocks($content);
        return $this->extract_content_structure($blocks);
    }

    private function extract_content_structure($blocks) {
        $structure = array();

        foreach ($blocks as $block) {
            if (empty($block['blockName'])) {
                continue;
            }

            $block_structure = array(
                'type' => $block['blockName'],
                'content' => ''
            );

            switch ($block['blockName']) {
                case 'core/heading':
                    $block_structure['content'] = wp_strip_all_tags($block['innerHTML']);
                    $block_structure['level'] = $block['attrs']['level'] ?? 2;
                    break;

                case 'core/paragraph':
                    $block_structure['content'] = wp_strip_all_tags($block['innerHTML']);
                    break;

                case 'core/list':
                    $block_structure['items'] = $this->extract_list_items($block['innerHTML']);
                    break;

                case 'core/quote':
                    $block_structure['content'] = wp_strip_all_tags($block['innerHTML']);
                    break;
            }

            $structure[] = $block_structure;
        }

        return $structure;
    }

    private function extract_list_items($html) {
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $items = $dom->getElementsByTagName('li');
        $list_items = array();

        foreach ($items as $item) {
            $list_items[] = wp_strip_all_tags($item->textContent);
        }

        return $list_items;
    }
}