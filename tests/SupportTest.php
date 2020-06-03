<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{
    public function testIsNull()
    {
        $this->assertTrue(is_null([null, 'a' => ['b' => null]]));
    }
}
