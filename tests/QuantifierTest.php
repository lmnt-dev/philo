<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class QuantifierTest extends TestCase
{
    public function testEvery()
    {
        $f = every(is_string);
        $this->assertTrue(is($f, []));
        $this->assertTrue(is($f, ['a', 'b']));
        $this->assertFalse(is($f, ['a', 'b', 3]));
    }
    public function testSome()
    {
        $f = some(is_string);
        $this->assertFalse(is($f, []));
        $this->assertTrue(is($f, ['a', 'b']));
        $this->assertTrue(is($f, ['a', 'b', 3]));
        $this->assertFalse(is($f, [1, 2, 3]));
    }
}
