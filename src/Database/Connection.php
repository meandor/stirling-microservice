<?php
namespace Database;

interface Connection
{
    public static function Instance();

    public function getDbLink();
}