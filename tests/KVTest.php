<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class KVTest extends TestCase
{
    public function testKV()
    {
        $f = kv(identity);
        $this->assertEquals($f(1), null);
        $this->assertEquals($f('a'), null);
        $this->assertEquals(
            $f([1, 'a']),
            [1, 'a']
        );
        $this->assertEquals(
            $f((object)['a' => 1, 'b' => 2]),
            (object) ['a' => 1, 'b' => 2]
        );
    }
    public function testMapNonIterable()
    {
        $f = map('is_string');
        $this->assertEquals($f(null), null);
        $this->assertEquals($f(false), null);
        $this->assertEquals($f(true), null);
        $this->assertEquals($f('a'), null);
        $this->assertEquals($f(1), null);
    }
    public function testMapArray()
    {
        $f = map('is_string');
        $this->assertEquals(
            $f([1, '2']),
            [false, true]
        );
    }
    public function testMapObject()
    {
        $f = map('is_string');
        $this->assertEquals(
            $f((object) ['a' => 1, 'b' => '2']),
            (object) ['a' => false, 'b' => true]
        );
    }
    public function testMapKeyPath()
    {
        $f = map(fn ($x, $k) => $k, ['...path']);
        $this->assertEquals(
            $f(['a' => 1, 'b' => [2, 3]]),
            ['a' => ['...path', 'a'], 'b' => ['...path', 'b']]
        );
    }
    public function testReduce()
    {
        $f = fn ($x, $y) => $x + $y;
        $this->assertEquals(reduce($f)([1, 2, 3]), 6);
    }
    public function testFilter()
    {
        $f = filter('is_string');
        $this->assertEquals(
            $f([1, '2', 3, 4, '5']),
            [1 => '2', 4 => '5']
        );
    }
    public function testPluck()
    {
        $f = pluck('a', 'b');
        $this->assertEquals(
            $f([1, 'a' => 2, 'b' => 3]),
            ['a' => 2, 'b' => 3]
        );
    }
}
