<?php

namespace Stirling\Database;

abstract class MysqlRepository implements Repository
{

    protected $key;

    protected $table;

    protected $link;

    /**
     * MysqlRepository constructor.
     * @param $key
     * @param $table
     */
    public function __construct($key, $table)
    {
        $this->key = $key;
        $this->table = $table;
        $this->link = MysqlConnection::Instance()->getDbLink();
    }

    public function fetchAll()
    {
        $query = "SELECT * FROM `" . $this->table . "`";
        $res = $this->link->query($query);
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function findOne($key)
    {
        // TODO: Implement findOne() method.
    }

    public function search($constraint)
    {
        // TODO: Implement search() method.
    }

    public function delete($key)
    {
        // TODO: Implement delete() method.
    }

    public function save($object)
    {
        // TODO: Implement save() method.
    }
}