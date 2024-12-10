<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Charger WP Mock
define('ABSPATH', true);
define('WP_DEBUG', true);

// Définir la fonction parse_blocks si elle n'existe pas
if (!function_exists('parse_blocks')) {
    function parse_blocks($content) {
        if (empty($content)) {
            return array();
        }
        // Simulation plus robuste de la fonction parse_blocks
        return array(
            array(
                'blockName' => 'core/paragraph',
                'attrs' => array(),
                'innerHTML' => $content,
                'innerContent' => array($content),
                'innerBlocks' => array()
            )
        );
    }
}

// Charger les classes nécessaires
require_once dirname(__DIR__) . '/includes/processors/class-abstract-content-processor.php';
require_once dirname(__DIR__) . '/includes/processors/class-markdown-processor.php';
require_once dirname(__DIR__) . '/includes/utils/class-abstract-utility.php';
require_once dirname(__DIR__) . '/includes/utils/class-markdown-converter.php';
