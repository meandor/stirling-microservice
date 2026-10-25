<?php

namespace Stirling\Core;

class TestingConfig extends Config
{
    public static function destroy(): void
    {
        parent::$instance = null;
    }
}
