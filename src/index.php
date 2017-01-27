<?php

require_once('./Core/Autoloader.php');

use \Core\Autoloader;
use \Core\Router;

Autoloader::register();

Router::init();

Router::add('info', function () {
    phpinfo();
});

Router::run();
