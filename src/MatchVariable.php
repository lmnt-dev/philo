<?php

declare(strict_types=1);

namespace philo;

/**
 * Prolog-esque variables for philo/match
 */
class MatchVariable {

  /**
   * @var callable
   */
  protected $constraint;

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
    if (!$this->constraint || is($x, $this->constraint)) {
      $this->x = $x;
      return $x;
    }
    return $this->x = null;
  }

  /**
   * Constrain variable type
   */
  function constrain(callable $f)
  {
    $this->constraint = f($f);
    return $this;
  }
}