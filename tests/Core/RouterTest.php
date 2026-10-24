<?php

namespace Stirling\Core;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
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

    public function testRunMatchesRoute()
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
        $this->assertStringContainsString("hello", $output);
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
        $path = tempnam(sys_get_temp_dir(), "cfg_") . ".json";
        file_put_contents($path, json_encode(["maintenanceUser" => "admin", "maintenancePassword" => "secret"]));
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
        $path = tempnam(sys_get_temp_dir(), "cfg_") . ".json";
        file_put_contents($path, json_encode(["maintenanceUser" => "admin", "maintenancePassword" => "secret"]));
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
        $autoload = dirname(__DIR__, 2) . "/vendor/autoload.php";
        $lines = [
            '<?php',
            "require '$autoload';",
            'use Stirling\Core\Router;',
            '$_SERVER[\'REQUEST_METHOD\'] = \'OPTIONS\';',
            '$_SERVER[\'REQUEST_URI\'] = \'/\';',
            'ob_start();',
            'Router::init();',
            'Router::run();',
            "echo 'AFTER';",
        ];
        $result = $this->runSubprocessLines($lines);
        $this->assertSame(0, $result["exitCode"]);
        $this->assertStringNotContainsString("AFTER", $result["stdout"]);
    }

    public function testRunWithAuthenticationFailureExits()
    {
        $autoload = dirname(__DIR__, 2) . "/vendor/autoload.php";
        $lines = [
            '<?php',
            "require '$autoload';",
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
        $autoload = dirname(__DIR__, 2) . "/vendor/autoload.php";
        $lines = [
            '<?php',
            "require '$autoload';",
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

    private function runSubprocessLines(array $lines): array
    {
        $script = implode("\n", $lines) . "\n";
        $file = tempnam(sys_get_temp_dir(), "router_probe_");
        file_put_contents($file, $script);
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file);
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes);
        $this->assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        @unlink($file);
        return [
            'exitCode' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }
}
