<?php
class Processor_Registry {
    private static $processors = [];

    public static function register_processor($processor_class) {
        if (!is_subclass_of($processor_class, 'Abstract_Content_Processor')) {
            throw new Exception("La classe $processor_class doit étendre Abstract_Content_Processor");
        }
        self::$processors[$processor_class::get_type()] = $processor_class;
    }

    public static function get_processor($type) {
        if (!isset(self::$processors[$type])) {
            throw new Exception("Processeur non trouvé pour le type: $type");
        }
        $class = self::$processors[$type];
        return new $class();
    }

    public static function get_available_types() {
        return array_keys(self::$processors);
    }

    public static function get_instruction_options() {
        $options = [];
        foreach (self::$processors as $class) {
            $options[] = [
                'label' => $class::get_label(),
                'value' => $class::get_type(),
                'uses_chatgpt' => $class::uses_chatgpt()
            ];
        }
        return $options;
    }
} 