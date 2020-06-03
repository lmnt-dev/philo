<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class ConnectiveTest extends TestCase
{
    public function testAll()
    {
        $f = all('is_int', gt(3), lt(5));
        $this->assertTrue(is_right($f(4)));
        $this->assertTrue(is_left($f(5)));
        $this->assertTrue(is_left($f('string')));
    }
    public function testAny()
    {
        $f = any('is_int', 'is_array');
        $this->assertTrue(is_right($f(4)));
        $this->assertTrue(is_right($f([])));
        $this->assertTrue(is_left($f('string')));
    }
    public function testNot()
    {
        $f = not(all('is_int', gt(3), lt(5)));
        $this->assertTrue(is_left($f(4)));
        $this->assertTrue(is_right($f(5)));
        $this->assertTrue(is_right($f('string')));

        $f = not(any('is_int', 'is_array'));
        $this->assertTrue(is_left($f(4)));
        $this->assertTrue(is_left($f([])));
        $this->assertTrue(is_right($f('string')));
    }
}
