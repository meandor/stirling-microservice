<?php
namespace Stirling\Database;

/**
 * Interface for the data mapper
 *
 * This is more or less a factory that creates IEntities.
 *
 * @package Stirling\Database
 */
interface IDataMapper
{
    /**
     * Returns created IEntity
     * @param $dbData array raw data from the database
     * @return IEntity
     */
    function createEntity($dbData);
}