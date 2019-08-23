<?php

namespace Stirling\Core;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testAddRoute()
    {
        Router::add("POST", "foobar", function () {
            return "1337";
        });
        $actual = Router::$routes;
        $this->assertEquals(1, count($actual));
        $this->assertEquals("#^foobar$#", $actual[0]->getEndpointPattern());
        $this->assertNotEmpty($actual[0]->getCallback());
        $this->assertEquals("1337", call_user_func($actual[0]->getCallback()));
    }

    public function testAdd404Route()
    {
        Router::setNotFound(function () {
            return "404";
        });
        $actual = Router::$notFound;
        $this->assertEquals("404", call_user_func($actual));
    }
}
