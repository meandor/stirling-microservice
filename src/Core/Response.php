<?php

namespace Stirling\Core;

class Response
{
    /**
     * @var int code
     */
    private $code;

    /**
     * @var array body
     */
    private $body;

    /**
     * Response constructor.
     * @param int $code
     * @param array $body
     */
    public function __construct(int $code, array $body)
    {
        $this->code = $code;
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return false|string
     */
    public function getBody()
    {
        return json_encode($this->body, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
    }
}
