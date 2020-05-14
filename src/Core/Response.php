<?php

namespace Stirling\Core;

use RuntimeException;

class Response
{
    /**
     * @var int code
     */
    private $code;

    /**
     * @var mixed entity
     */
    private $entity;

    /**
     * Response constructor.
     * @param int $code
     * @param mixed $entity
     */
    public function __construct(int $code, $entity)
    {
        $this->code = $code;
        $this->entity = $entity;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        if (is_null($this->entity)) {
            return "";
        }

        $json = json_encode($this->entity, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException("Failed serializing json for: " . $this->entity);
        }
        return $json;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
