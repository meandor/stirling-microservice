<?php
namespace Stirling\Database;

/**
 * Interface for Repository pattern
 *
 * It uses the IDataMapper to map the data from the database into IEntities.
 * See more at: https://martinfowler.com/eaaCatalog/repository.html
 *
 * @package Stirling\Database
 */
interface IRepository
{
    /**
     * Returns all IEntities from the database
     * @return array
     */
    public function fetchAll(): array;

    /**
     * Returns the IEntity with the specified key from the database or null
     * @param $key string
     * @return null|IEntity
     */
    public function findOne($key): ?IEntity;

    /**
     * Deletes the given IEntity
     * @param $entity IEntity
     * @return boolean True if deleted, false if not
     */
    public function delete(IEntity $entity): bool;

    /**
     * Saves and returns the given IEntity
     * @param IEntity $entity
     * @return null|IEntity
     */
    public function save(IEntity $entity): ?IEntity;
}