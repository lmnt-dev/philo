<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class ConnectiveTest extends TestCase
{
    public function testAll()
    {
        $f = all('is_int', gt(3), lt(5));
        $this->assertTrue($f(4));
        $this->assertFalse($f(5));
        $this->assertFalse($f('string'));
    }
    public function testAny()
    {
        $f = any('is_int', 'is_array');
        $this->assertTrue($f(4));
        $this->assertTrue($f([]));
        $this->assertFalse($f('string'));
    }
    public function testNot()
    {
        $f = not(all('is_int', gt(3), lt(5)));
        $this->assertFalse($f(4));
        $this->assertTrue($f(5));
        $this->assertTrue($f('string'));

        $f = not(any('is_int', 'is_array'));
        $this->assertFalse($f(4));
        $this->assertFalse($f([]));
        $this->assertTrue($f('string'));
    }
}
