<?php
namespace Stirling\Core;

use \PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testAddRoute()
    {
        Router::add("foobar", function () {
            return "1337";
        });
        $actual = Router::$routes;
        $this->assertEquals(1, count($actual));
        $this->assertEquals("foobar", $actual[0]["expression"]);
        $this->assertNotEmpty($actual[0]["function"]);
        $this->assertEquals("1337", call_user_func($actual[0]["function"]));
    }

    public function testAdd404Route()
    {
        Router::add404(function () {
            return "404";
        });
        $actual = Router::$routes404;
        $this->assertEquals(1, count($actual));
        $this->assertEquals("404", call_user_func($actual[0]));
    }
}
