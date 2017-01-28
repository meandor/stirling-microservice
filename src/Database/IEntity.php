<?php
namespace Stirling\Database;

/**
 * Interface for Entities.
 *
 * An Entity is a class created from database data.
 *
 * @package Stirling\Database
 */
interface IEntity
{
    public function getKey();

    public function getProperties();
}