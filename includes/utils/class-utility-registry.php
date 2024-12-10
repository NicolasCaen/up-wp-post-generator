<?php
class Utility_Registry {
    private static $utilities = [];

    public static function register_utility($utility_class) {
        if (!is_subclass_of($utility_class, 'Abstract_Utility')) {
            throw new Exception("La classe doit étendre Abstract_Utility");
        }
        self::$utilities[$utility_class::get_type()] = $utility_class;
    }

    public static function get_utility($type) {
        if (!isset(self::$utilities[$type])) {
            throw new Exception("Utilitaire non trouvé : $type");
        }
        $class = self::$utilities[$type];
        return new $class();
    }

    public static function get_available_types() {
        return array_keys(self::$utilities);
    }
} 