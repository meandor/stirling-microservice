<?php

namespace Stirling\Core;


use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testShouldGetJsonEncodedEntity()
    {
        $response = new Response(200, array("message" => "foo"));

        $actual = $response->getBody();
        $expected = "{\n    \"message\": \"foo\"\n}";

        $this->assertEquals($expected, $actual);
    }

    public function testShouldGetRawEntity()
    {
        $entity = array("message" => "foo");
        $response = new Response(200, $entity);

        $actual = $response->getEntity();
        $expected = $entity;

        $this->assertEquals($expected, $actual);
    }

    public function testShouldReturnEmptyStringForNull()
    {
        $response = new Response(200, null);

        $actual = $response->getBody();
        $expected = "";

        $this->assertEquals($expected, $actual);
    }
}
