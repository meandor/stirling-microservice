<?php
namespace Stirling\Core;

use InvalidArgumentException;

class Config
{

    protected static $instance;

    private $properties;

    /**
     * Config constructor.
     * @param string $configPath
     */
    private function __construct($configPath)
    {
        $this->properties = Array();
        if (file_exists($configPath)) {
            $json = json_decode(file_get_contents($configPath), true);
            if ($json != null) {
                $this->properties = $json;
            }
        }
    }

    public static function instance($configPath = "./default.json")
    {
        if (self::$instance == null) {
            self::$instance = new Config($configPath);
        }
        return self::$instance;
    }

    public function __get($name)
    {
        if (key_exists($name, $this->properties)) {
            return $this->properties[$name];
        } else {
            throw new InvalidArgumentException("Property '" . $name . "' does not exist");
        }
    }
}
