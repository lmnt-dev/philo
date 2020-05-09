<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{
    public function testSlice()
    {
        $this->assertEquals(['a', 'b'], slice(['a', 'b']));
        $this->assertEquals(['b'], slice(['a', 'b'], -1));
        $this->assertEquals('b', slice('ab', -1));
    }
}
