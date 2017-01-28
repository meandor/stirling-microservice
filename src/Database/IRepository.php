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
    public function fetchAll();

    /**
     * Returns the IEntity with the specified key from the database or null
     * @param $key string
     * @return IEntity
     */
    public function findOne($key);

    /**
     * Returns an array with IEntities that satisfy the constraint
     * @param $constraint
     * @return mixed
     */
    public function search($constraint);

    /**
     * Deletes the given IEntity
     * @param $entity IEntity
     * @return boolean True if deleted, false if not
     */
    public function delete($entity);

    /**
     * Saves and returns the given IEntity
     * @param $entity
     * @return mixed
     */
    public function save($entity);
}