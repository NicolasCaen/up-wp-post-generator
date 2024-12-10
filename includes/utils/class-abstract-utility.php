<?php
abstract class Abstract_Utility {
    abstract public static function get_type();
    abstract public static function get_label();
    abstract public function process($content);
} 