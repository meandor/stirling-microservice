<?php

namespace Stirling\Core;

use PHPUnit\Framework\TestCase;

class ConfigurationMaskerTest extends TestCase
{
    public function testMaskIntegerKey()
    {
        $masker = new ConfigurationMasker();

        $actual = $masker->mask([123 => "hunter2"]);

        $this->assertEquals([123 => "hunter2"], $actual);
    }

    public function testFlatSensitiveKeyIsMasked()
    {
        $masker = new ConfigurationMasker();

        $actual = $masker->mask(["db_password" => "hunter2"]);

        $this->assertEquals(["db_password" => "********"], $actual);
    }

    public function testNonSensitiveKeyIsUnchanged()
    {
        $masker = new ConfigurationMasker();

        $actual = $masker->mask(["keyboard_layout" => "us"]);

        $this->assertEquals(["keyboard_layout" => "us"], $actual);
    }

    public function testCamelCaseBoundaryIsSplitBeforeMatching()
    {
        $masker = new ConfigurationMasker();

        $actual = $masker->mask(["secretKey" => "abc"]);

        $this->assertEquals(["secretKey" => "********"], $actual);
    }

    public function testNestedSensitiveKeysAreMasked()
    {
        $masker = new ConfigurationMasker();

        $actual = $masker->mask([
            "service" => [
                "name" => "api",
                "api-key" => "top-secret",
            ],
        ]);

        $expected = [
            "service" => [
                "name" => "api",
                "api-key" => "********",
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testCustomPatternsOverrideTheDefaults()
    {
        $masker = new ConfigurationMasker(["passphrase"]);

        $actual = $masker->mask(["admin_passphrase" => "open sesame"]);

        $this->assertEquals(["admin_passphrase" => "********"], $actual);
    }

    public function testSensitiveEarlierSegmentMasksKey()
    {
        $masker = new ConfigurationMasker();

        $actual = $masker->mask(["password_field" => "value"]);

        $this->assertEquals(["password_field" => "********"], $actual);
    }

    public function testUppercaseSensitiveSegmentIsMasked()
    {
        $masker = new ConfigurationMasker();

        $actual = $masker->mask(["API_KEY" => "value"]);

        $this->assertEquals(["API_KEY" => "********"], $actual);
    }
}
