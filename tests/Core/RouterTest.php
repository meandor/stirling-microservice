<?php

namespace Stirling\Core;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    use SubprocessRunner;

    protected function setUp(): void
    {
        Router::$routes = [];
        Router::setNotFound(fn() => null);
        Router::$path = null;
        unset($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"], $_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]);
        Config::destroy();
    }

    public function testAddRoute()
    {
        Router::add("POST", "foobar", function () {
            return "1337";
        });
        $actual = Router::$routes;
        $this->assertEquals(1, count($actual));
        $this->assertEquals("#^foobar$#", $actual[0]->getEndpointPattern());
        $this->assertNotEmpty($actual[0]->getCallback());
        $this->assertEquals("1337", call_user_func($actual[0]->getCallback()));
    }

    public function testAdd404Route()
    {
        Router::setNotFound(function () {
            return "404";
        });
        $actual = Router::$notFound;
        $this->assertEquals("404", call_user_func($actual->getCallback()));
    }

    public function testInitSetsPathFromUri()
    {
        $_SERVER["REQUEST_URI"] = "/some/path/";
        Router::init();
        $this->assertSame("some/path", Router::$path);
    }

    public function testInitTrimsEmptyPath()
    {
        $_SERVER["REQUEST_URI"] = "/";
        Router::init();
        $this->assertSame("", Router::$path);
    }

    public function testInitWithNoRequestUri()
    {
        $_SERVER["REQUEST_URI"] = "";
        Router::init();
        $this->assertSame("", Router::$path);
    }

    public function testRunHealthRoute()
    {
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["REQUEST_URI"] = "/health";
        ob_start();
        Router::init();
        Router::run();
        $output = ob_get_clean();
        $this->assertStringContainsString("HEALTHY", $output);
    }

    public function testRunNotFoundBranch()
    {
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["REQUEST_URI"] = "/nonexistent";
        Router::init();
        Router::setNotFound(function () {
            echo "CUSTOM-404";
        });
        ob_start();
        Router::run();
        $output = ob_get_clean();
        $this->assertStringContainsString("CUSTOM-404", $output);
    }

    public function testWithAuthenticationAllowsWhenNoCreds()
    {
        $called = false;
        $closure = Router::withAuthentication("", "", function () use (&$called) {
            $called = true;
        });
        $closure();
        $this->assertTrue($called);
    }

    public function testRunInfoRouteWithValidCredentials()
    {
        $path = $this->createMaintenanceConfig();
        Config::instance($path);
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["REQUEST_URI"] = "/info";
        $_SERVER["PHP_AUTH_USER"] = "admin";
        $_SERVER["PHP_AUTH_PW"] = "secret";
        ob_start();
        Router::init();
        Router::run();
        $output = ob_get_clean();
        $this->assertStringContainsString("PHP Version", $output);
        @unlink($path);
    }

    public function testInitWithoutCredentials()
    {
        $_SERVER["REQUEST_URI"] = "/";
        Router::init();
        $this->assertTrue(true);
    }

    public function testInitWithCredentialsConfiguresAuthenticatedRoutes()
    {
        $path = $this->createMaintenanceConfig();
        Config::instance($path);
        ob_start();
        $_SERVER["REQUEST_URI"] = "/";
        Router::init();
        ob_end_clean();
        $this->assertSame("admin", Config::instance()->maintenanceUser);
        @unlink($path);
    }

    public function testRunOptionsMethodExits()
    {
        $lines = [
            '<?php',
            'namespace Stirling\Core;',
            'require ' . var_export(phpunit_bootstrap_path(), true) . ';',
            '$RECORDED = [];',
            'function header($string) { global $RECORDED; $RECORDED[] = $string; }',
            '$_SERVER[\'REQUEST_METHOD\'] = \'OPTIONS\';',
            '$_SERVER[\'REQUEST_URI\'] = \'/\';',
            '$_SERVER[\'HTTP_ACCESS_CONTROL_REQUEST_HEADERS\'] = \'X-Test\';',
            'register_shutdown_function(function () { global $RECORDED; echo \'RECORDED:\' . implode(chr(44), $RECORDED); });',
            'ob_start();',
            'Router::init();',
            'Router::run();',
            "echo 'AFTER';",
        ];
        $result = $this->runSubprocessLines($lines);
        $this->assertSame(0, $result["exitCode"]);
        $this->assertStringNotContainsString("AFTER", $result["stdout"]);
        $this->assertStringContainsString("Access-Control-Allow-Origin: *", $result["stdout"]);
        $this->assertStringContainsString("Access-Control-Allow-Credentials: true", $result["stdout"]);
        $this->assertStringContainsString("Access-Control-Max-Age: 86400", $result["stdout"]);
        $this->assertStringContainsString("Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS, DELETE", $result["stdout"]);
    }

    public function testRouteMatchingCallbackArguments()
    {
        Router::add("GET", "greet/(.+)", function ($name) {
            echo "hello {$name}";
        });
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["REQUEST_URI"] = "/greet/world";
        ob_start();
        Router::init();
        Router::run();
        $output = ob_get_clean();
        $this->assertSame("hello world", $output);
    }

    public function testNotFoundHandlerReceivesPath()
    {
        $lines = [
            '<?php',
            'require ' . var_export(phpunit_bootstrap_path(), true) . ';',
            'use Stirling\Core\Router;',
            '$_SERVER[\'REQUEST_METHOD\'] = \'GET\';',
            '$_SERVER[\'REQUEST_URI\'] = \'/missing\';',
            'ob_start();',
            'Router::init();',
            'Router::setNotFound(function ($path) {',
            '    echo \'NOTFOUND:\' . $path;',
            '});',
            'Router::run();',
            "echo 'AFTER';",
        ];
        $result = $this->runSubprocessLines($lines);
        $this->assertStringContainsString("NOTFOUND:missing", $result["stdout"]);
    }

    public function testAuthGuardDeniesOnPasswordMismatch()
    {
        $result = $this->runAuthGuard("admin", "secret", ["PHP_AUTH_USER" => "admin", "PHP_AUTH_PW" => "wrong"]);

        $this->assertSame(0, $result["exitCode"]);
        $this->assertStringContainsString("Not Authorized", $result["stdout"]);
        $this->assertStringNotContainsString("ALLOWED", $result["stdout"]);
    }

    public function testAuthGuardDeniesOnUsernameMismatch()
    {
        $result = $this->runAuthGuard("admin", "secret", ["PHP_AUTH_USER" => "wrong", "PHP_AUTH_PW" => "secret"]);

        $this->assertSame(0, $result["exitCode"]);
        $this->assertStringContainsString("Not Authorized", $result["stdout"]);
        $this->assertStringNotContainsString("ALLOWED", $result["stdout"]);
    }

    public function testAuthGuardDeniesWhenCredentialsAreMissing()
    {
        $result = $this->runAuthGuard("admin", "secret", []);

        $this->assertSame(0, $result["exitCode"]);
        $this->assertStringContainsString("Not Authorized", $result["stdout"]);
        $this->assertStringNotContainsString("ALLOWED", $result["stdout"]);
    }

    public function testAuthGuardAllowsWhenUsernameIsEmpty()
    {
        $result = $this->runAuthGuard("", "secret", ["PHP_AUTH_USER" => "attacker", "PHP_AUTH_PW" => "secret"]);

        $this->assertSame(0, $result["exitCode"]);
        $this->assertStringContainsString("ALLOWED", $result["stdout"]);
        $this->assertStringNotContainsString("Not Authorized", $result["stdout"]);
    }

    public function testAuthGuardAllowsWhenPasswordIsEmpty()
    {
        $result = $this->runAuthGuard("admin", "", ["PHP_AUTH_USER" => "admin", "PHP_AUTH_PW" => "attacker"]);

        $this->assertSame(0, $result["exitCode"]);
        $this->assertStringContainsString("ALLOWED", $result["stdout"]);
        $this->assertStringNotContainsString("Not Authorized", $result["stdout"]);
    }

    public function testStatusRouteExits()
    {
        $path = $this->createMaintenanceConfig();
        $lines = [
            '<?php',
            'require ' . var_export(phpunit_bootstrap_path(), true) . ';',
            'use Stirling\Core\Config;',
            'Config::instance(' . escapeshellarg($path) . ');',
            'use Stirling\Core\Router;',
            '$_SERVER[\'REQUEST_METHOD\'] = \'GET\';',
            '$_SERVER[\'REQUEST_URI\'] = \'/status\';',
            '$_SERVER[\'PHP_AUTH_USER\'] = \'admin\';',
            '$_SERVER[\'PHP_AUTH_PW\'] = \'secret\';',
            'ob_start();',
            'Router::init();',
            'Router::run();',
            "echo 'AFTER';",
        ];
        $result = $this->runSubprocessLines($lines);
        $this->assertSame(0, $result["exitCode"]);
        $this->assertStringContainsString("\"statusDetails\":[]", $result["stdout"]);
        @unlink($path);
    }

    public function testRunWithAuthenticationFailureExits()
    {
        $lines = [
            '<?php',
            'require ' . var_export(phpunit_bootstrap_path(), true) . ';',
            '$closure = Stirling\Core\Router::withAuthentication(\'admin\', \'secret\', function () {',
            "    echo 'SHOULD-NOT-RUN';",
            '});',
            '$_SERVER[\'PHP_AUTH_USER\'] = \'wrong\';',
            '$_SERVER[\'PHP_AUTH_PW\'] = \'wrong\';',
            '$closure();',
            "echo 'AFTER';",
        ];
        $result = $this->runSubprocessLines($lines);
        $this->assertSame(0, $result["exitCode"]);
        $this->assertStringContainsString("Not Authorized", $result["stdout"]);
        $this->assertStringNotContainsString("SHOULD-NOT-RUN", $result["stdout"]);
        $this->assertStringNotContainsString("AFTER", $result["stdout"]);
    }

    public function testRunDefaultNotFoundExits()
    {
        $lines = [
            '<?php',
            'require ' . var_export(phpunit_bootstrap_path(), true) . ';',
            'use Stirling\Core\Router;',
            '$_SERVER[\'REQUEST_METHOD\'] = \'GET\';',
            '$_SERVER[\'REQUEST_URI\'] = \'/nonexistent\';',
            'ob_start();',
            'Router::init();',
            'Router::run();',
            "echo 'AFTER';",
        ];
        $result = $this->runSubprocessLines($lines);
        $this->assertSame(0, $result["exitCode"]);
        $this->assertStringNotContainsString("AFTER", $result["stdout"]);
    }

    private function createMaintenanceConfig(): string
    {
        $path = tempnam(sys_get_temp_dir(), "cfg_") . ".json";
        file_put_contents($path, json_encode(["maintenanceUser" => "admin", "maintenancePassword" => "secret"]));
        return $path;
    }

    private function runAuthGuard(string $username, string $password, array $credentials): array
    {
        $lines = [
            '<?php',
            'namespace Stirling\\Core;',
            'require ' . var_export(phpunit_bootstrap_path(), true) . ';',
        ];
        foreach ($credentials as $name => $value) {
            $lines[] = '$_SERVER[' . var_export($name, true) . '] = ' . var_export($value, true) . ';';
        }
        $lines = array_merge($lines, [
            '$closure = Router::withAuthentication(' . var_export($username, true) . ', ' . var_export($password, true) . ', function () {',
            "    echo 'ALLOWED';",
            '});',
            '$closure();',
            "echo 'AFTER';",
        ]);

        return $this->runSubprocessLines($lines);
    }
}
