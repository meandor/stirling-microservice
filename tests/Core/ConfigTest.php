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

    const CONFIG_JSON = __DIR__ . "/../resources/default.json";

    protected function tearDown()
    {
        parent::tearDown();
        TestingConfig::destroy();

        try {
            unlink(self::CONFIG_JSON);
        } catch (Exception $e) {

        }
    }


    public function testCreateConfigSingleton()
    {
        $expected = TestingConfig::instance(self::CONFIG_JSON);
        $actual = TestingConfig::instance(self::CONFIG_JSON);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNotExistentConfigProperty()
    {
        $actual = TestingConfig::instance(self::CONFIG_JSON);
        $actual->foobar;
        $this->expectExceptionMessage("Property 'foobar' does not exist");
    }

    public function testGetExistentPropertyValue()
    {
        file_put_contents(self::CONFIG_JSON, "{\"foo\":\"bar\"}");
        var_dump(file_get_contents(self::CONFIG_JSON));
        $actual = TestingConfig::instance(self::CONFIG_JSON);
        $this->assertEquals("bar", $actual->foo);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetMissingPropertyValueFromCorrectFile()
    {
        file_put_contents(self::CONFIG_JSON, "{\"foo\":\"bar\"}");
        $actual = TestingConfig::instance(self::CONFIG_JSON);
        $actual->foobar;
        $this->expectExceptionMessage("Property 'foobar' does not exist");
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMalformedJson()
    {
        file_put_contents(self::CONFIG_JSON, "{foo\":\"bar\"}");
        $actual = TestingConfig::instance(self::CONFIG_JSON);
        $actual->foo;
        $this->expectExceptionMessage("Property 'foo' does not exist");
    }
}
