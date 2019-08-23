<?php

namespace Stirling\Core;

use InvalidArgumentException;

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
        if ($_SERVER['REQUEST_METHOD'] === HttpMethods::OPTIONS) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header("Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS, DELETE");
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            header('Access-Control-Max-Age: 86400');
            exit(0);
        }

        $matchingEndpoints = array_filter(self::$routes, function ($route) {
            $endpointMatches = preg_match($route->getEndpointPattern(), self::$path, $matches);
            $httpMethodMatches = $route->getHttpMethod() === $_SERVER['REQUEST_METHOD'];
            return $endpointMatches && $httpMethodMatches;
        });

        if (!empty($matchingEndpoints)) {
            $route = array_values($matchingEndpoints)[0];
            preg_match($route->getEndpointPattern(), self::$path, $matches);
            array_shift($matches);
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            call_user_func_array($route->getCallback(), $matches);
        } else {
            call_user_func_array(self::$notFound->getCallback(), Array(self::$path));
        }
    }

    public static function withAuthentication($username, $password, $callback)
    {
        return function () use ($username, $password, $callback) {
            if ((!empty($username) && !empty($password)) && (
                    !isset($_SERVER['PHP_AUTH_USER']) ||
                    $_SERVER['PHP_AUTH_USER'] != $username ||
                    $_SERVER['PHP_AUTH_PW'] != $password)
            ) {
                header('WWW-Authenticate: Basic realm="Realm"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Not Authorized';
                exit(0);
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
            return array("username" => $username, "password" => $password);
        } catch (InvalidArgumentException $exception) {
            error_log("No maintenance user or password set");
            return array("username" => null, "password" => null);
        }
    }

    private static function addDefaultRoutes()
    {
        self::add('GET', 'health', function () {
            echo "HEALTHY";
        });

        $config = Config::instance();
        $maintenanceCredentials = self::getCredentials($config);
        $username = $maintenanceCredentials["username"];
        $password = $maintenanceCredentials["password"];

        self::add('GET', 'info', self::withAuthentication($username, $password, function () {
            phpinfo();
        }));

        self::add('GET', 'status', self::withAuthentication($username, $password, function () {
            AppStatus::instance()->buildStatusPage();
        }));

        self::setNotFound(function () {
            http_response_code(404);
            exit(0);
        });
    }
}
