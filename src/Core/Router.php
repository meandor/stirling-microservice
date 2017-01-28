<?php

namespace Stirling\Core;

class Router
{
    public static $routes = Array();
    public static $routes404 = Array();
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

    public static function add($expression, $function)
    {
        array_push(self::$routes, Array(
            'expression' => $expression,
            'function' => $function
        ));
    }

    public static function add404($function)
    {
        array_push(self::$routes404, $function);
    }

    public static function run()
    {
        $route_found = false;

        foreach (self::$routes as $route) {
            //Add 'find string start' automatically
            $route['expression'] = '^' . $route['expression'];

            //Add 'find string end' automatically
            $route['expression'] = $route['expression'] . '$';

            //check match
            if (preg_match('#' . $route['expression'] . '#', self::$path, $matches)) {
                array_shift($matches);//Always remove first element. This contains the whole string
                call_user_func_array($route['function'], $matches);
                $route_found = true;
            }
        }

        if (!$route_found) {
            foreach (self::$routes404 as $route404) {
                call_user_func_array($route404, Array(self::$path));
            }
        }
    }

    private static function addDefaultRoutes()
    {
        self::add('info', function () {
            phpinfo();
        });

        self::add('health', function () {
            echo "HEALTHY";
        });
    }
}