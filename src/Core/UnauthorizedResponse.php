<?php

namespace Stirling\Core;

class UnauthorizedResponse
{
    public function send(): void
    {
        header('WWW-Authenticate: Basic realm="Realm"');
        header("HTTP/1.0 401 Unauthorized");
        echo "Not Authorized";
        exit(0);
    }
}
