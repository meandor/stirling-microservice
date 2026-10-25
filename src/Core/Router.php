<?php

namespace Stirling\Core;

use InvalidArgumentException;

class Router
{
    public static $routes = [];

    /**
     * @var Route
     */
    public static $notFound;
    public static $path;

    public static function init()
    {
        $parsed_url = parse_url($_SERVER["REQUEST_URI"]);

        if (isset($parsed_url["path"])) {
            self::$path = trim($parsed_url["path"], "/");
        } else {
            self::$path = "";
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
        self::$notFound = new Route("", "", $callback);
    }

    public static function run()
    {
        $corsHandler = new CorsHandler();
        $corsHandler->addCommonHeaders();

        if ($_SERVER["REQUEST_METHOD"] === HttpMethods::OPTIONS) {
            $corsHandler->handleOptionsRequest();
        }

        $route = self::matchRoute();
        if ($route !== null) {
            preg_match($route->getEndpointPattern(), self::$path, $matches);
            array_shift($matches);
            call_user_func_array($route->getCallback(), $matches);
        } else {
            call_user_func_array(self::$notFound->getCallback(), [self::$path]);
        }
    }

    private static function matchRoute()
    {
        $matchingEndpoints = array_filter(self::$routes, function ($route) {
            $endpointMatches = preg_match($route->getEndpointPattern(), self::$path, $matches);
            $httpMethodMatches = $route->getHttpMethod() === $_SERVER["REQUEST_METHOD"];
            return $endpointMatches && $httpMethodMatches;
        });

        if (empty($matchingEndpoints)) {
            return null;
        }

        return array_values($matchingEndpoints)[0];
    }

    public static function withAuthentication($username, $password, $callback)
    {
        return function () use ($username, $password, $callback) {
            if ((!empty($username) && !empty($password)) && (
                !isset($_SERVER["PHP_AUTH_USER"])
                    || $_SERVER["PHP_AUTH_USER"] != $username
                    || $_SERVER["PHP_AUTH_PW"] != $password
            )
            ) {
                new UnauthorizedResponse()->send();
            } else {
                call_user_func($callback);
            }
        };
    }

    private static function getCredentials($config)
    {
        try {
            $username = $config->maintenanceUser;
            $password = $config->maintenancePassword;
            return ["username" => $username, "password" => $password];
        } catch (\Exception $exception) { // Property access goes through __get(), which PHPStan cannot analyse. @phpstan-ignore catch.neverThrown
            return ["username" => null, "password" => null];
        }
    }

    private static function addDefaultRoutes()
    {
        self::addHealthRoute();
        self::addAuthenticatedRoutes();
        self::setNotFound(function () {
            http_response_code(404);
            exit(0);
        });
    }

    private static function addHealthRoute()
    {
        self::add("GET", "health", function () {
            echo "HEALTHY";
        });
    }

    private static function addAuthenticatedRoutes()
    {
        $config = Config::instance();
        $maintenanceCredentials = self::getCredentials($config);
        $username = $maintenanceCredentials["username"];
        $password = $maintenanceCredentials["password"];

        self::add("GET", "info", self::withAuthentication($username, $password, function () {
            phpinfo();
        }));

        self::add("GET", "status", self::withAuthentication($username, $password, function () {
            AppStatus::instance()->buildStatusPage();
        }));
    }
}
