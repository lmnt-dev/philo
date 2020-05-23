<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class PredicateTest extends TestCase
{
    public function testEq()
    {
        $this->assertTrue(eq(3)(3));
    }
    public function testLt()
    {
        $this->assertTrue(lt(3)(2));
        $this->assertFalse(lt(3)(3));
    }
    public function testLte()
    {
        $this->assertTrue(lte(3)(2));
        $this->assertTrue(lte(3)(3));
    }
    public function testGt()
    {
        $this->assertTrue(gt(3)(4));
        $this->assertFalse(gt(3)(3));
    }
    public function testGte()
    {
        $this->assertTrue(gte(3)(3));
        $this->assertTrue(gte(3)(4));
    }
    public function testIn()
    {
        $this->assertTrue(in([3])(3));
    }
    public function testMaybe()
    {
        $T = [
            'a' => is_int,
            'b' => maybe(is_bool)
        ];
        $this->assertTrue(is($T, ['a' => 1]));
        $this->assertTrue(is($T, ['a' => 1, 'b' => false]));
        $this->assertTrue(is($T, ['a' => 1, 'c' => 2]));
        $this->assertFalse(is($T, ['a' => 1, 'b' => 2]));
    }
}
