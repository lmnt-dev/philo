<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testKV()
    {
        $f = kv(identity);
        $this->assertNull($f(1));
        $this->assertNull($f('a'));
        $this->assertEquals(
            [1, 'a'],
            $f([1, 'a'])
        );
        $this->assertEquals(
            (object) ['a' => 1, 'b' => 2],
            $f((object)['a' => 1, 'b' => 2])
        );
    }
    public function testMapNonIterable()
    {
        $f = map('is_string');
        $this->assertNull($f(null));
        $this->assertNull($f(false));
        $this->assertNull($f(true));
        $this->assertNull($f('a'));
        $this->assertNull($f(1));
    }
    public function testMapArray()
    {
        $f = map('is_string');
        $this->assertEquals(
            [false, true],
            $f([1, '2'])
        );
    }
    public function testMapObject()
    {
        $f = map('is_string');
        $this->assertEquals(
            (object) ['a' => false, 'b' => true],
            $f((object) ['a' => 1, 'b' => '2'])
        );
    }
    public function testMapKeyPath()
    {
        $f = map(fn ($x, $k) => $k, ['...path']);
        $this->assertEquals(
            ['a' => ['...path', 'a'], 'b' => ['...path', 'b']],
            $f(['a' => 1, 'b' => [2, 3]])
        );
    }
    public function testReduce()
    {
        $f = fn ($x, $y) => $x + $y;
        $g = fn($r, $x, $k) => $r . " $k:$x";
        $x = [1, 2, 3];
        $this->assertEquals(6, reduce($f)($x));
        $this->assertEquals(' 0:1 1:2 2:3', reduce($g)($x));
    }
    public function testFilter()
    {
        $f = filter('is_string');
        $this->assertEquals(
            [1 => '2', 4 => '5'],
            $f([1, '2', 3, 4, '5'])
        );
    }
    public function testPluck()
    {
        $f = pluck('a', 'b');
        $this->assertEquals(
            [['a' => 2, 'b' => 3], ['a' => 5]],
            $f([
                [1, 'a' => 2, 'b' => 3],
                [4, 'a' => 5]
            ])
        );
    }
}
