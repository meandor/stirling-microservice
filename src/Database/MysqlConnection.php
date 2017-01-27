<?php
namespace Database;

use mysqli;

class MysqlConnection implements Connection
{

    private static $connection;
    private $dbLink;

    private function __construct()
    {
        $config = json_decode(file_get_contents("./db.json"), true);
        $this->dbLink = new mysqli($config["host"], $config["user"], $config["password"], $config["database"]);
        if ($this->dbLink->connect_errno) {
            $err = "Error: Failed to make a MySQL connection, here is why: \n";
            $err .= "Errno: " . $this->dbLink->connect_errno . "\n";
            $err .= "Error: " . $this->dbLink->connect_error . "\n";
            error_log($err);
            exit;
        }
    }

    public static function Instance()
    {
        if (self::$connection == null) {
            self::$connection = new MysqlConnection();
        }

        return self::$connection;
    }

    /**
     * @return mysqli
     */
    public function getDbLink()
    {
        return $this->dbLink;
    }


}