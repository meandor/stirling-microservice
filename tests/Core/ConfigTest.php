<?php

namespace Stirling\Core;

use Exception;
use PHPUnit\Framework\TestCase;

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

    protected function tearDown(): void
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

    public function testNotExistentConfigProperty()
    {
        $this->expectExceptionMessage("Property 'foobar' does not exist");
        $actual = TestingConfig::instance(self::CONFIG_JSON);
        $actual->foobar;
    }

    public function testGetExistentPropertyValue()
    {
        file_put_contents(self::CONFIG_JSON, "{\"foo\":\"bar\"}");

        $actual = TestingConfig::instance(self::CONFIG_JSON);
        $this->assertEquals("bar", $actual->foo);
    }

    public function testGetMissingPropertyValueFromCorrectFile()
    {
        $this->expectExceptionMessage("Property 'foobar' does not exist");
        file_put_contents(self::CONFIG_JSON, "{\"foo\":\"bar\"}");
        $actual = TestingConfig::instance(self::CONFIG_JSON);
        $actual->foobar;
    }

    public function testMalformedJson()
    {
        $this->expectExceptionMessage("Property 'foo' does not exist");
        file_put_contents(self::CONFIG_JSON, "{foo\":\"bar\"}");
        $actual = TestingConfig::instance(self::CONFIG_JSON);
        $actual->foo;
    }
}
