<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class QuantifierTest extends TestCase
{
    public function testEvery()
    {
        $f = every('is_string');
        $this->assertTrue($f([]));
        $this->assertTrue($f(['a', 'b']));
        $this->assertFalse($f(['a', 'b', 3]));
    }
    public function testSome()
    {
        $f = some('is_string');
        $this->assertFalse($f([]));
        $this->assertTrue($f(['a', 'b']));
        $this->assertTrue($f(['a', 'b', 3]));
        $this->assertFalse($f([1, 2, 3]));
    }
}
