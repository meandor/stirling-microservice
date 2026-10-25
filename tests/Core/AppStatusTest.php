<?php

namespace Stirling\Core;

use PHPUnit\Framework\TestCase;

class AppStatusTest extends TestCase
{
    use SubprocessRunner;

    public const okStatus = ["name" => "foobar", "description" => "foobar message", "status" => "ok"];

    public const errorStatus = ["name" => "foobar2", "description" => "error message", "status" => "error"];

    protected function setUp(): void
    {
        file_put_contents(__DIR__ . "/../resources/default.json", "{\"name\":\"foobar\",\"version\":\"1.3.3.7\",\"config1\":\"value\"}");
        Config::destroy();
        TestingConfig::instance(__DIR__ . "/../resources/default.json");
        AppStatus::instance()->reset();
    }

    public function testStatusBitwiseAssignment()
    {
        $status = AppStatus::instance();
        $status->reset();
        $status->registerStatus("failing", "failing message", function () {
            return false;
        });
        $status->registerStatus("passing", "passing message", function () {
            return true;
        });
        $this->assertSame("error", $status->getStatusOverview());

        $status->reset();
        $status->registerStatus("passing", "passing message", function () {
            return true;
        });
        $this->assertSame("ok", $status->getStatusOverview());
    }

    public function testStatusResponseHeaders()
    {
        $path = tempnam(sys_get_temp_dir(), "status_") . ".json";
        file_put_contents($path, json_encode(["name" => "x","version" => "1","config1" => "v"]));
        $lines = [
            '<?php',
            'namespace Stirling\Core;',
            'require ' . var_export(phpunit_bootstrap_path(), true) . ';',
            '$RECORDED = [];',
            'function header($string) { global $RECORDED; $RECORDED[] = $string; }',
            '$path = ' . escapeshellarg($path) . ';',
            'Config::instance($path);',
            'ob_start();',
            'AppStatus::instance()->buildStatusPage();',
            'echo \'RECORDED:\' . implode(chr(44), $RECORDED);',
        ];
        $result = $this->runSubprocessLines($lines);
        $this->assertStringContainsString("Content-Type: application/json", $result["stdout"]);
        @unlink($path);
    }

    public function testSingletonAppStatus()
    {
        file_put_contents(__DIR__ . "/../resources/default.json", "{\"name\":\"foobar\",\"version\":\"1.3.3.7\",\"config1\":\"value\"}");
        Config::instance(__DIR__ . "/../resources/default.json");
        $actual = AppStatus::instance();
        $this->assertEquals(AppStatus::instance(), $actual);
    }

    public function testAggregateEmptyStatus()
    {
        $actual = AppStatus::instance();
        $this->assertEquals([], $actual->aggregateStatusDetails());
    }

    public function testEmptyStatusArray()
    {
        $actual = AppStatus::instance()->getStatusArray();
        $expected = [
            "application" => [
                "name" => "foobar",
                "version" => "1.3.3.7"],
            "git" => null,
            "configuration" => ["config1" => "value"],
            "status" => "ok",
            "statusDetails" => []];
        $this->assertEquals($expected, $actual);
    }

    public function testRegisteringOneStatus()
    {
        $status = AppStatus::instance();
        $status->registerStatus("foobar", "foobar message", function () {
            return true;
        });
        $actual = $status->aggregateStatusDetails();
        $expected[] = self::okStatus;
        $this->assertEquals($expected, $actual);
    }

    public function testStatusOverview()
    {
        $status = AppStatus::instance();
        $status->registerStatus("foobar", "foobar message", function () {
            return true;
        });
        $this->assertEquals("ok", $status->getStatusOverview());
    }

    public function testOneItemInStatusArray()
    {
        $status = AppStatus::instance();
        $status->registerStatus("foobar", "foobar message", function () {
            return true;
        });
        $actual = $status->getStatusArray();
        $expected = [
            "application" => [
                "name" => "foobar",
                "version" => "1.3.3.7"],
            "git" => null,
            "configuration" => ["config1" => "value"],
            "status" => "ok",
            "statusDetails" => [0 => self::okStatus]];
        $this->assertEquals($expected, $actual);
    }

    public function testRegisteringErrorStatus()
    {
        $status = AppStatus::instance();
        $status->registerStatus("foobar", "foobar message", function () {
            return true;
        });
        $status->registerStatus("foobar2", "error message", function () {
            return false;
        });
        $actual = $status->aggregateStatusDetails();
        $expected[] = self::okStatus;
        $expected[] = self::errorStatus;
        $this->assertEquals($expected, $actual);
    }

    public function testStatusErrorOverview()
    {
        $status = AppStatus::instance();
        $status->registerStatus("foobar", "foobar message", function () {
            return true;
        });
        $status->registerStatus("foobar2", "error message", function () {
            return false;
        });
        $this->assertEquals("error", $status->getStatusOverview());
    }

    public function testSensitiveConfigurationValueIsMasked()
    {
        file_put_contents(__DIR__ . "/../resources/default.json", "{\"name\":\"foobar\",\"version\":\"1.3.3.7\",\"db_password\":\"hunter2\"}");
        TestingConfig::destroy();
        TestingConfig::instance(__DIR__ . "/../resources/default.json");

        $actual = AppStatus::instance()->getStatusArray();

        $this->assertEquals("********", $actual["configuration"]["db_password"]);
    }

    public function testBuildStatusPageJsonContainsNoPlaintextSecret()
    {
        file_put_contents(__DIR__ . "/../resources/default.json", "{\"name\":\"foobar\",\"version\":\"1.3.3.7\",\"db_password\":\"hunter2\"}");
        TestingConfig::destroy();
        TestingConfig::instance(__DIR__ . "/../resources/default.json");

        ob_start();
        AppStatus::instance()->buildStatusPage();
        $output = ob_get_clean();

        $this->assertStringContainsString("********", $output);
        $this->assertStringNotContainsString("hunter2", $output);
    }

    public function testStatusArray()
    {
        $status = AppStatus::instance();
        $status->registerStatus("foobar", "foobar message", function () {
            return true;
        });
        $status->registerStatus("foobar2", "error message", function () {
            return false;
        });
        $actual = $status->getStatusArray();
        $expected = [
            "application" => [
                "name" => "foobar",
                "version" => "1.3.3.7"],
            "git" => null,
            "configuration" => ["config1" => "value"],
            "status" => "error",
            "statusDetails" => [0 => self::okStatus, 1 => self::errorStatus]];
        $this->assertEquals($expected, $actual);
    }
}
