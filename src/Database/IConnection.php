<?php
namespace Stirling\Database;

/**
 * Interface for the database connection
 *
 * Singleton Pattern should be used
 *
 * @package Stirling\Database
 */
interface IConnection
{
    /**
     * Method to get the connection instance.
     * @return IConnection Database Connection instance
     */
    public static function Instance(): IConnection;

    /**
     * Returns the db link
     * @return mixed
     */
    public function getDbLink();
}