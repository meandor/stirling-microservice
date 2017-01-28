<?php
namespace Stirling\Database;


interface IRepository
{
    public function fetchAll();

    public function findOne($key);

    public function search($constraint);

    public function delete($key);

    public function save($object);
}