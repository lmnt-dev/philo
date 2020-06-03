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
            to_array(map($item))
        )(['barb', 'bob']);

        $this->assertEquals(':[*barb* *bob*]:', $text);
    }
    public function testFanOut()
    {
        $f = fanout('strtoupper', 'ord', identity);
        $this->assertEquals(['Q', 113, 'q'], $f('q'));
    }
    public function testPipe()
    {
        $f = pipe('strtoupper', 'ord', 'sqrt');
        $this->assertEquals(9, $f('Q'));
    }
    public function testSpread()
    {
        $f = spread(fn ($a, $b, $c) => "$a:$b:$c");
        $this->assertEquals('1:2:3', $f([1,2,3]));
    }
}
