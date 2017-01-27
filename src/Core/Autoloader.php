<?php
namespace Core;

class Autoloader
{
    public function __construct()
    {
        spl_autoload_register(array($this, 'load_class'));
    }

    public static function register()
    {
        new Autoloader();
    }

    public function load_class($class_name)
    {
        $file = str_replace("\\", "/", $class_name) . ".php";

        if (file_exists($file)) {
            require_once($file);
        }
    }
}