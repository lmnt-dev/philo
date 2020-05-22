<?php

declare(strict_types=1);

namespace philo;

/**
* Prolog-like variables for philo\match
*/
class MatchVariable {
    
    /**
    * @var callable
    */
    protected $constraint;

    /**
     * Create multiple variables
     * @param int $n
     */
    static function create($n = 1) {
        return array_map(
            fn () => new self(),
            array_fill(0, $n, null)
        );
    }

    /**
    * Return matched value
    */
    function __invoke()
    {
        return $this->x;
    }
    
    /**
    * Match value
    */
    function is($x)
    {
        if (!$this->constraint || is($this->constraint, $x)) {
            $this->x = $x;
            return $x;
        }
        return $this->x = null;
    }
    
    /**
    * Constrain variable type
    */
    function constrain($T)
    {
        $this->constraint = $T;
        return $this;
    }
}