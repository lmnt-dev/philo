<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testMapNonIterable()
    {
        $f = map('is_string');
        $this->assertNull($f(null));
        $this->assertNull($f(false));
        $this->assertNull($f(true));
        $this->assertNull($f('a'));
        $this->assertNull($f(1));
        $this->assertNull($f((object) ['a' => 1, 'b' => '2']));
    }
    public function testMapArray()
    {
        $f = to_array(map(is_string));
        $this->assertEquals(
            [false, true],
            $f([1, '2'])
        );
    }
    public function testMapKeyPath()
    {
        $f = to_array(map(fn ($x, $k) => $k, ['...path']));
        $this->assertEquals(
            [['...path', 'a'], ['...path', 'b']],
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
        $f = to_array(filter(is_string), true);
        $this->assertEquals(
            [1 => '2', 4 => '5'],
            $f([1, '2', 3, 4, '5'])
        );
    }
    public function testPick()
    {
        $f = to_array(pick('a'), true);
        $this->assertEquals(
            ['a' => 2],
            $f([1, 'a' => 2, 'b' => 3])
        );
    }
    public function testPluck()
    {
        $f = to_array(pluck('a', 'b'), true);
        $this->assertEquals(
            [['a' => 2, 'b' => 3], ['a' => 5]],
            $f([
                [1, 'a' => 2, 'b' => 3],
                [4, 'a' => 5]
            ])
        );
    }
    public function testSlice()
    {
        $this->assertEquals(['a', 'b'], slice()(['a', 'b']));
        $this->assertEquals(['b'], slice(-1)(['a', 'b']));
        $this->assertEquals('b', slice(-1)('ab'));
    }
}
