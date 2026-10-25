<?php

namespace Stirling\Core;

use Exception;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public const CONFIG_JSON = __DIR__ . "/../resources/default.json";

    protected function setUp(): void
    {
        TestingConfig::destroy();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        TestingConfig::destroy();

        file_put_contents(self::CONFIG_JSON, "{}");
    }

    public function testDefaultConfigPathMaterializes()
    {
        $defaultPath = __DIR__ . "/../../resources/default.json";
        $backup = file_exists($defaultPath) ? file_get_contents($defaultPath) : null;

        try {
            if (!is_dir(dirname($defaultPath))) {
                mkdir(dirname($defaultPath), 0o777, true);
            }
            file_put_contents($defaultPath, json_encode(["config1" => "materialized"]));
            TestingConfig::destroy();

            $config = TestingConfig::instance();

            $this->assertEquals("materialized", $config->config1);
        } finally {
            TestingConfig::destroy();
            if ($backup === null) {
                @unlink($defaultPath);
            } else {
                file_put_contents($defaultPath, $backup);
            }
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
