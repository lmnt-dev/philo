<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class CreationTest extends TestCase
{
    public function testIsLeft()
    {
        // boolean false and incorrect types go to the left
        $this->assertTrue(is_left(is_int('9')));
        $this->assertTrue(is_left(create([is_int], ['9'])));
    }
    public function testIsRight()
    {
        // boolean true and correct types go to the right
        $this->assertTrue(is_right(is_int(9)));
        $this->assertTrue(is_right(create([is_int], [9])));
    }
    public function testLeftVal()
    {
        // lval contains incorrect type, rval is null
        $this->assertEquals([false, null], [lval(false), rval(false)]);
        $x = create(is_bool, false);
        $this->assertEquals([false, null], [lval($x), rval($x)]);
    }
    public function testRightVal()
    {
        // lval is null, rval contains expected type
        $this->assertEquals([null, false], [lval(false), rval(false)]);
        $x = create(is_bool, false);
        $this->assertEquals([null, false], [lval($x), rval($x)]);
    }
    public function testArray()
    {
        $T = [is_bool, is_string, is_int];
        $this->assertTrue(is_right(create($T, [true, 'a', 1])));
        $x = create($T, [true, 'a', '1']);
        $this->assertTrue(is_left($x));
        $this->assertEquals([null, null, '1'], lval($x));
        $this->assertEquals([true, 'a', null], rval($x));
    }
    public function testArrayNested()
    {
        $T = [is_string, ['x' => is_int, [is_string]]];
        $x = create($T, [1, ['x' => 2, [1]]]);
        $this->assertTrue(is_left($x));
        $this->assertEquals([1, ['x' => null, [1]]], lval($x));
        $this->assertEquals([null, ['x' => 2, [null]]], rval($x));
    }
    public function testArrayMaybe()
    {
        $T = [is_string, maybe(is_int)];
        $this->assertTrue(is_left(create($T, ['1', '2'])));
        $this->assertTrue(is_right(create($T, ['1', 2])));
        $this->assertTrue(is_right(create($T, ['1'])));
        $this->assertTrue(is_right(create($T, ['1', null, 8])));
    }
    public function testArrayNotStrict()
    {
        $T = [is_string];
        $this->assertTrue(is_right(create($T, ['1', 2])));
    }
    public function testArrayStrict()
    {
        $T = strict([is_string]);
        $this->assertTrue(is_left(create($T, ['1', 2])));
    }
    public function testArrayStrictWithMaybe()
    {
        $T = strict([is_string, maybe(is_int)]);
        $this->assertTrue(is_left(create($T, ['1', '2'])));
        $this->assertTrue(is_right(create($T, ['1', 2])));
        $this->assertTrue(is_right(create($T, ['1'])));
        $this->assertTrue(is_left(create($T, ['1', null, 8])));
    }
    public function testBool()
    {
        $T = [is_bool, is_bool];
        $this->assertTrue(is_right(create($T, [true, false])));
    }
}
