<?php

namespace Stirling\Core;

class Router
{
    public static $routes = Array();

    /**
     * @var Route
     */
    public static $notFound;
    public static $path;

    public static function init()
    {
        $parsed_url = parse_url($_SERVER['REQUEST_URI']);

        if (isset($parsed_url['path'])) {
            self::$path = trim($parsed_url['path'], '/');
        } else {
            self::$path = '';
        }

        self::addDefaultRoutes();
    }

    /**
     * @param $httpMethod string HTTP method like POST, GET, ...
     * @param $endpointPattern string Regex to match an incoming request
     * @param $callback callable Function to execute when $method and $expression match
     */
    public static function add($httpMethod, $endpointPattern, $callback)
    {
        $route = new Route($httpMethod, $endpointPattern, $callback);
        array_push(self::$routes, $route);
    }

    public static function setNotFound($callback)
    {
        self::$notFound = $callback;
    }

    public static function run()
    {
        $route_found = false;

        foreach (self::$routes as $route) {
            //check match
            $endpointMatches = preg_match($route->getEndpointPattern(), self::$path, $matches);
            if ($endpointMatches && $route->getHttpMethod() === $_SERVER['REQUEST_METHOD']) {
                array_shift($matches);//Always remove first element. This contains the whole string
                call_user_func_array($route->getCallback(), $matches);
                $route_found = true;
                break;
            }
        }

        if (!$route_found) {
            call_user_func_array(self::$notFound->getCallback(), Array(self::$path));
        }
    }

    private static function addDefaultRoutes()
    {
        self::add('GET', 'health', function () {
            echo "HEALTHY";
        });

        self::add('GET', 'info', function () {
            phpinfo();
        });

        self::add('GET', 'status', function () {
            AppStatus::instance()->buildStatusPage();
        });
    }
}
