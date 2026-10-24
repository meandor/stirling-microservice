<?php

namespace Stirling\Core;

use PHPUnit\Framework\TestCase;

class AppStatusTest extends TestCase
{
    public const okStatus = ["name" => "foobar", "description" => "foobar message", "status" => "ok"];

    public const errorStatus = ["name" => "foobar2", "description" => "error message", "status" => "error"];

    protected function setUp(): void
    {
        file_put_contents(__DIR__ . "/../resources/default.json", "{\"name\":\"foobar\",\"version\":\"1.3.3.7\",\"config1\":\"value\"}");
        Config::destroy();
        TestingConfig::instance(__DIR__ . "/../resources/default.json");
        AppStatus::instance()->reset();
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
