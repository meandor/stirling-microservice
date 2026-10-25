<?php

namespace Stirling\Core;

class CorsHandler
{
    public function addCommonHeaders(): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
    }

    public function handleOptionsRequest(): void
    {
        header("Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS, DELETE");
        header("Access-Control-Allow-Headers: {$_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]}");
        exit(0);
    }
}
