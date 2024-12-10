<?php
class Processor_Registry {
    private static $processors = [];

    public static function register_processor($processor_class) {
        // Vérifier si c'est un processeur ChatGPT ou un utilitaire
        if (!is_subclass_of($processor_class, 'Abstract_ChatGPT_Processor') && 
            !is_subclass_of($processor_class, 'Abstract_Content_Processor')) {
            $class_name = is_string($processor_class) ? $processor_class : get_class($processor_class);
            throw new Exception(
                sprintf(
                    "La classe '%s' doit étendre soit 'Abstract_ChatGPT_Processor' soit 'Abstract_Content_Processor'",
                    $class_name
                )
            );
        }
        
        self::$processors[$processor_class::get_type()] = $processor_class;
    }

    public static function get_processor($type) {
        if (!isset(self::$processors[$type])) {
            throw new Exception("Processeur non trouvé : $type");
        }
        return new self::$processors[$type]();
    }

    public static function get_available_types() {
        return array_keys(self::$processors);
    }

    public static function requires_prompt($type) {
        if (!isset(self::$processors[$type])) {
            throw new Exception("Processeur non trouvé : $type");
        }
        
        // Vérifier si le processeur est un utilitaire
        return is_subclass_of(self::$processors[$type], 'Abstract_ChatGPT_Processor');
    }

    public static function get_instruction_options() {
        $options = [];
        foreach (self::$processors as $type => $processor_class) {
            $options[] = [
                'value' => $type,
                'label' => $processor_class::get_label(),
                'requiresPrompt' => self::requires_prompt($type)
            ];
        }
        return array_values($options);
    }
} 