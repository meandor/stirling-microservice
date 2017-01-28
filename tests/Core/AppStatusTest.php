<?php
namespace Stirling\Core;

use Exception;
use \PHPUnit\Framework\TestCase;

class AppStatusTest extends TestCase
{
    const okStatus = array("name" => "foobar", "description" => "foobar message", "status" => "ok");

    const errorStatus = array("name" => "foobar2", "description" => "error message", "status" => "error");

    public function testSingletonAppStatus()
    {
        $actual = AppStatus::instance();
        $this->assertEquals(AppStatus::instance(), $actual);
    }

    public function testAggregateEmptyStatus()
    {
        $actual = AppStatus::instance();
        $this->assertEquals(array(), $actual->aggregateStatusDetails());
    }

    public function testEmptyStatusArray()
    {
        try {
            unlink("./default.json");
        } catch (Exception $e) {

        }
        file_put_contents("./default.json", "{\"name\":\"foobar\",\"version\":\"1.3.3.7\",\"config1\":\"value\"}");
        $actual = AppStatus::instance()->getStatusArray();
        $expected = array(
            "application" => array(
                "name" => "foobar",
                "version" => "1.3.3.7"),
            "git" => null,
            "configuration" => array("config1" => "value"),
            "status" => "ok",
            "statusDetails" => array());
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
        $this->assertEquals("ok", AppStatus::instance()->getStatusOverview());
    }

    public function testOneItemInStatusArray()
    {
        try {
            unlink("./default.json");
        } catch (Exception $e) {

        }
        file_put_contents("./default.json", "{\"name\":\"foobar\",\"version\":\"1.3.3.7\",\"config1\":\"value\"}");
        $actual = AppStatus::instance()->getStatusArray();
        $expected = array(
            "application" => array(
                "name" => "foobar",
                "version" => "1.3.3.7"),
            "git" => null,
            "configuration" => array("config1" => "value"),
            "status" => "ok",
            "statusDetails" => array(0 => self::okStatus));
        $this->assertEquals($expected, $actual);
    }

    public function testRegisteringErrorStatus()
    {
        $status = AppStatus::instance();
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
        $this->assertEquals("error", AppStatus::instance()->getStatusOverview());
    }

    public function testStatusArray()
    {
        try {
            unlink("./default.json");
        } catch (Exception $e) {

        }
        file_put_contents("./default.json", "{\"name\":\"foobar\",\"version\":\"1.3.3.7\",\"config1\":\"value\"}");
        $actual = AppStatus::instance()->getStatusArray();
        $expected = array(
            "application" => array(
                "name" => "foobar",
                "version" => "1.3.3.7"),
            "git" => null,
            "configuration" => array("config1" => "value"),
            "status" => "error",
            "statusDetails" => array(0 => self::okStatus, 1 => self::errorStatus));
        $this->assertEquals($expected, $actual);
    }
}
