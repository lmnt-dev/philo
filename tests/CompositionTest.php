<?php

declare(strict_types=1);

namespace philo;

use PHPUnit\Framework\TestCase;

class CompositionTest extends TestCase
{
    public function testCompose()
    {
        $root = fn ($x) => ":$x:";
        $list = fn ($x) => '[' . implode(' ', $x) . ']';
        $item = fn ($x) => "*$x*";

        $text = compose(
            $root,
            $list,
            map($item)
        )(['barb', 'bob']);

        $this->assertEquals(':[*barb* *bob*]:', $text);
    }
    public function testPipe()
    {
        $f = pipe('strtoupper', 'ord', 'sqrt');
        $this->assertEquals($f('Q'), 9);
    }
    public function testFanOut()
    {
        $f = fanout('strtoupper', 'ord', identity);
        $this->assertEquals($f('q'), ['Q', 113, 'q']);
    }
}
