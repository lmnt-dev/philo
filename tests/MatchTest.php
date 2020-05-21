<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

interface FakeInterface {}
class FakeClass implements FakeInterface {}

class MatchTest extends TestCase
{
    public function testMatchLiteral()
    {
        $f = match(
            'a', 'b',
            2, fn ($x) => ":$x",
            'd', identity
        );
        $this->assertEquals('b', $f('a'));
        $this->assertEquals(null, $f('2'));
        $this->assertEquals(':2', $f(2));
        $this->assertEquals('d', $f('d'));
    }
    public function testMatchPredicate()
    {
        $f = match(
            is_string, fn ($x) => ":$x",
            is_int, fn ($x) => "#$x"
        );
        $this->assertEquals(':a', $f('a'));
        $this->assertEquals('#1', $f(1));
    }
    public function testMatchArray()
    {
        $f = match(
            [1, 2], 'specific',
            is_array, 'generic'
        );
        $this->assertEquals('specific', $f([1, 2]));
        $this->assertEquals('generic', $f([]));
    }
    public function testMatchStruct()
    {
        $f = match(
            ['x' => is_int, 'y' => [
                'z' => is_string
            ]], 'struct'
        );
        $this->assertEquals('struct', $f(['x' => 1, 'y' => ['z' => 'a']]));
    }
    public function testMatchClass()
    {
        $f = match(
            FakeClass::class, 'specific',
            FakeInterface::class, 'generic'
        );
        $this->assertEquals('specific', $f(new FakeClass()));
        $this->assertEquals('generic', $f(new class implements FakeInterface {}));
    }
    public function testMatchNested()
    {
        $f = match('is_array', map(match(
            3, 'three',
            k(gte(1)), fn ($x, $k) => "$k:$x"
        )));
        $this->assertEquals(
            [null, '1:2', 'three'],
            $f([1, 2, 3])
        );
    }
    public function testMatchRecursiveInput()
    {
        $kv = fn ($x, $k) => implode('/', (array) $k) . ":$x";
        
        $f = rmatch(
            4, identity,
            k('a', 0, 1), $kv,
            k(['b', 'c'], -2), $kv
        );

        $tree = [
            'a' => 1,
            'apple' => 2,
            'b' => [
                'c' => 3,
                'd' => 4,
                'e' => ['b' => ['c' => 5]],
                'f' => 6
            ]
        ];

        $this->assertEquals([
            'a' => 'a:1',
            'apple' => 'apple:2',
            'b' => [
                'c' => 'b/c:3',
                'd' => 4,
                'e' => ['b' => ['c' => 'b/e/b/c:5']],
                'f' => null
            ]
        ], $f($tree));
    }
    public function testMatchTuple()
    {
        $f = match(
            [is_int, is_string, gt(2)], 'tuple',
            is_array, 'generic'
        );
        $this->assertEquals('tuple', $f([1, '2', 3]));
        $this->assertEquals('generic', $f([1, '2']));
    }
    public function testMatchVariable()
    {
        [$X] = MatchVariable::create();

        $exists = filter(not(is_null));
        $query = fn ($type, $value) =>
            $exists(rmatch($type, $value)([
                ['A', ['B'], 'X'],
                ['A', 'B', 'X'],
                ['A', 'C', 'Z']
            ]));

        $result = $query(['A', $X, 'Z'], [$X, $X]);
        $this->assertEquals([2 => ['C', 'C']], $result);

        $result = $query(['A', $X->constrain(is_array), 'X'], $X);
        $this->assertEquals([0 => ['B']], $result);

        $result = $query(['A', $X->constrain(is_string), 'X'], $X);
        $this->assertEquals([1 => 'B'], $result);
    }
    public function testMatchVariableFanOut()
    {
        $X = new MatchVariable;
        $Y = new MatchVariable;

        $exists = filter(not(is_null));
        $query = fn ($type, $value) =>
            $exists(rmatch($type, $value)([
                ['A', ['B'], 'X'],
                ['A', 'B', 'Y'],
                ['A', 3, 'Z'],
                ['A', 1, 'T'],
            ]));

        $X->constrain(any(
            is_string,
            all(is_int, gt(2))
        ));

        $result = $query(
            ['A', $X, $Y],
            fanout($X, $Y)
        );

        $this->assertEquals([
            1 => ['B', 'Y'],
            2 => [3, 'Z']
        ], $result);
    }
}
