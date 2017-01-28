<?php
namespace Stirling\Database;

interface IConnection
{
    public static function Instance();

    public function getDbLink();
}