<?php
namespace Stirling\Database;

interface Connection
{
    public static function Instance();

    public function getDbLink();
}