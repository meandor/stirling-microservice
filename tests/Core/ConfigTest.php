<?php
namespace Stirling\Core;

use Exception;
use InvalidArgumentException;
use \PHPUnit\Framework\TestCase;

class TestingConfig extends Config
{
    public static function destroy()
    {
        parent::$instance = null;
    }
}

class ConfigTest extends TestCase
{

    const DEFAULT_JSON = "./default.json";

    protected function tearDown()
    {
        parent::tearDown();
        TestingConfig::destroy();
    }


    public function testCreateConfigSingleton()
    {
        $expected = TestingConfig::instance();
        $actual = TestingConfig::instance();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNotExistentConfigProperty()
    {
        $actual = TestingConfig::instance();
        $actual->foobar;
        $this->expectExceptionMessage("Property 'foobar' does not exist");
    }

    public function testGetExistentPropertyValue()
    {
        try {
            unlink(self::DEFAULT_JSON);
        } catch (Exception $e) {

        }

        file_put_contents(self::DEFAULT_JSON, "{\"foo\":\"bar\"}");
        $actual = TestingConfig::instance();
        $this->assertEquals("bar", $actual->foo);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetMissingPropertyValueFromCorrectFile()
    {
        try {
            unlink(self::DEFAULT_JSON);
        } catch (Exception $e) {

        }

        file_put_contents(self::DEFAULT_JSON, "{\"foo\":\"bar\"}");
        $actual = TestingConfig::instance();
        $actual->foobar;
        $this->expectExceptionMessage("Property 'foobar' does not exist");
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMalformedJson()
    {
        try {
            unlink(self::DEFAULT_JSON);
        } catch (Exception $e) {

        }

        file_put_contents(self::DEFAULT_JSON, "{foo\":\"bar\"}");
        $actual = TestingConfig::instance();
        $actual->foo;
        $this->expectExceptionMessage("Property 'foo' does not exist");
    }

    public function testDifferentConfigJsonFile()
    {
        $configPath = "./foo.json";
        try {
            unlink(self::DEFAULT_JSON);
            unlink($configPath);
        } catch (Exception $e) {

        }

        file_put_contents($configPath, "{\"foobar\":\"42\"}");
        $actual = Config::instance($configPath);
        $this->assertEquals("42", $actual->foobar);
    }


}
