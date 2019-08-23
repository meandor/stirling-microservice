<?php

namespace Stirling\Core;

class Route
{
    private $httpMethod;

    private $endpointPattern;

    private $callback;

    /**
     * Route constructor.
     * @param $httpMethod string
     * @param $endpointPattern string
     * @param $callback callable
     */
    public function __construct($httpMethod, $endpointPattern, $callback)
    {
        $this->httpMethod = $httpMethod;
        $this->endpointPattern = '#^' . $endpointPattern . '$#';;
        $this->callback = $callback;
    }

    /**
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * @return string
     */
    public function getEndpointPattern(): string
    {
        return $this->endpointPattern;
    }

    /**
     * @return callable
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }
}
