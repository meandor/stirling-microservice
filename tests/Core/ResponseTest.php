<?php

namespace Stirling\Core;


use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testGetBody()
    {
        $response = new Response(200, array("message" => "foo"));

        $actual = $response->getBody();
        $expected = "{\n    \"message\": \"foo\"\n}";

        $this->assertEquals($expected, $actual);
    }
}
