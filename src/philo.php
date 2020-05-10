<?php

declare(strict_types=1);

/**
* ⏂ ρhilo
*
* Functional & friendly
*
* PHP version 7.4+
*
* @package    philo
* @author     Denny Shimkoski <denny@lmnt.dev>
* @copyright  2020
* @license    https://opensource.org/licenses/MIT MIT
*/

namespace philo;

/**
* Exported function strings
*/
const identity = 'philo\identity';
const is_array = 'is_array';
const is_bool = 'is_bool';
const is_callable = 'is_callable';
const is_int = 'is_int';
const is_float = 'is_float';
const is_nan = 'is_nan';
const is_null = 'philo\is_null_recursive';
const is_numeric = 'is_numeric';
const is_object = 'is_object';
const is_string = 'is_string';

/**
* Identity function
* 
* @param mixed $x
* @return mixed
*/
function identity($x)
{
    return $x;
}

/*-----------------------------
* COMPOSITION
*----------------------------*/

/**
* Call functions right to left, passing each result into the preceding function
* 
* Given `compose($f, $g)`, the result will be `$f($g())`
* 
* @param callable $fs
* @return callable
*/
function compose(callable ...$fs)
{
    return pipe(...array_reverse($fs));
}

/**
* Call functions left to right, passing each result into the next function
* 
* Given `pipe($f, $g)`, the result will be `$g($f())`
* 
* @param callable ...$fs
* @return callable
*/
function pipe(callable ...$fs)
{
    return fn ($x = null) => array_reduce($fs, fn ($g, $f) => $f($g), $x);
}

/**
* Distribute argument to multiple callables
*
* @param callable ...$fs
* @return callable
*/
function fanout (callable ...$fs) {
    return fn ($x) => array_map(fn ($f) => $f($x), $fs);
}

/*-----------------------------
* KV MANIPULATION
*----------------------------*/

/**
* Normalize key/val inputs for the given function
* 
* @param callable $f expecting associative array
* @return callable
*/
function kv(callable $f)
{
    return match(
        is_array, $f,
        is_object, fn ($x) => (object) $f(get_object_vars($x))
    );
}

/**
* Map inputs to outputs via the given function
* 
* @param callable $f
* @return callable
*/
function map(callable $f = null, array $path = null)
{
    $f = f($f);
    // use reduce to preserve keys
    return kv(fn (array $x) => array_reduce(
        array_keys($x),
        function ($r, $k) use ($f, $path, $x) {
            $r[$k] = $f($x[$k], $path ? array_merge($path, [$k]) : $k);
            return $r;
        }
    ));
}

/**
* Reduce inputs using the given reducer
* 
* @param callable $f
* @param mixed $initial
* @return callable
*/
function reduce(callable $f, $initial = null)
{
    return kv(fn (array $x) => array_reduce($x, $f, $initial));
}

/**
* Filter inputs using the given predicate
* 
* @param callable $f
* @return callable
*/
function filter(callable $f)
{
    return kv(fn (array $x) => array_filter($x, f($f), ARRAY_FILTER_USE_BOTH));
}

/**
* Filter inputs using the given keys
* 
* @param callable $f
* @return callable
*/
function pluck(string ...$keys)
{
    return filter(k(in($keys)));
}

/*-----------------------------
* MATCHING
*----------------------------*/

/**
* Match [...[$type, $value]] pairs
* 
* @param array ...$args
* @return callable
*/
function match(...$args)
{
    $f = function ($x, $k = null) use (&$f, $args) {
        foreach (array_chunk($args, 2) as [$T, $value]) {
            if (is($x, $T, $k)) {
                if (!is_callable($value)) {
                    return $value;
                }
                return f($value)($x, $k, $f);
            }
        }
        return null;
    };
    return $f;
}

/**
* Check if value (and/or optional key) represent an instance of the given type
* 
* @param mixed $x
* @param mixed $T
* @param mixed $k
* @return bool
*/
function is($x, $T, $k = null)
{
    if (is_array($x) && is_array($T)) {
        foreach ($T as $i => $U) {
            if (!isset($x[$i]) || !is($x[$i], $U, $i)) return false;
        }
        return true;
    } else if (is_type($T)) {
        if (!$T->is($x)) return false;
    } else if (!is_callable($T)) {
        if (is_string($T)) {
            if (class_exists($T) || interface_exists($T)) {
                if (!$x instanceof $T) return false;
            } else if ($x !== $T) {
                return false;
            }
        } else if ($x !== $T) {
            return false;
        }
    } else if (f($T)($x, $k) === false) {
        return false;
    }
    return true;
}

/**
* Match given key against input keys
* 
* @param string|int|array $path
* @param int $start
* @param int $length
* @return callable
*/
function k($path, int $start = 0, int $length = null)
{
    return function ($x, $k) use ($path, $start, $length) {
        if ($start || $length) {
            $k = slice($k, $start, $length);
        }
        return is_callable($path)
        ? $path($k)
        : is($k, $path);
    };
}

/**
* Extend `match` into tree structures
* 
* @return array
*/
function recurse()
{
    $recurse = fn ($x, $k, $f) => map(
        $f,
        $k !== null && !is_array($k) ? [$k] : $k
    )($x);
    return [
        is_array, $recurse,
        is_object, $recurse
    ];
}

/**
* Full recursive match of [...[$type, $value]] pairs
* 
* @param array ...$args
* @return callable
*/
function rmatch(...$pairs)
{
    return match(...$pairs, ...recurse());
}

/**
* Get query function for given data
* 
* @param array $data
* @return callable
*/
function db(array $data) {
    return function ($type, $value) use ($data) {
        return filter(not(is_null))(
            rmatch($type, $value)($data)
        );
    };
}

/*-----------------------------
* LOGICAL CONNECTIVES
*----------------------------*/

/**
* Return true if all predicates are true, i.e., logical AND
* 
* @param callable[] $fs
* @return callable
*/
function all(...$fs)
{
    $all = function ($fs, $x, $y) {
        foreach ($fs as $f) {
            if (!f($f)($x, $y)) {
                return false;
            }
        }
        return true;
    };
    return fn ($x, $k = null) => $all($fs, $x, $k);
}

/**
* Return true if any predicates are true, i.e., logical OR
* 
* @param callable[] $fs
* @return callable
*/
function any(...$fs)
{
    $any = function ($fs, $x, $k) {
        foreach ($fs as $f) {
            if (f($f)($x, $k)) {
                return true;
            }
        }
        return false;
    };
    return fn ($x, $k = null) => $any($fs, $x, $k);
}

/**
* Negate the given predicate, i.e., logical NOT
* 
* @param callable $f
* @return callable
*/
function not($f)
{
    return pipe($f, fn ($x) => !$x);
}

/*-----------------------------
* PREDICATES
*----------------------------*/

/**
* Return true if argument is strictly equal to input
* 
* @param mixed $y
* @return callable
*/
function eq($y)
{
    return fn ($x) => $x === $y;
}

/**
* Return true if argument is greater than input
* 
* @param mixed $y
* @return callable
*/
function gt($y)
{
    return fn ($x) => $x > $y;
}

/**
* Return true if argument is greater than or equal to input
* 
* @param mixed $y
* @return callable
*/
function gte($y)
{
    return fn ($x) => $x >= $y;
}

/**
* Return true if argument is less than input
* 
* @param mixed $y
* @return callable
*/
function lt($y)
{
    return fn ($x) => $x < $y;
}

/**
* Return true if argument is less than or equal to input
* 
* @param mixed $y
* @return callable
*/
function lte($y)
{
    return fn ($x) => $x <= $y;
}

/**
* Return true if input is found in array
* 
* @param mixed $y
* @return callable
*/
function in(array $in, $strict = true)
{
    return fn ($x) => in_array($x, $in, $strict);
}

/*-----------------------------
* QUANTIFIERS
*----------------------------*/

/**
* Return true if predicate is true for all inputs
* 
* @param callable $f
* @return callable
*/
function every(callable $f)
{
    $f = f($f);
    return kv(function (array $x) use ($f) {
        foreach ($x as $k => $v) {
            if (!$f($v, $k)) return false;
        }
        return true;
    });
}

/**
* Return true if predicate is true for at least one input
* 
* @param callable $f
* @return callable
*/
function some(callable $f)
{
    $f = f($f);
    return kv(function (array $x) use ($f) {
        foreach ($x as $k => $v) {
            if ($f($v, $k)) return true;
        }
        return false;
    });
}

/*-----------------------------
* SUPPORT
*----------------------------*/

/**
* Prevents strict mode errors by truncating args where necessary
* 
* Only applies to callable strings, other values will be returned as is
* 
* @param mixed $f
* @return mixed
*/
function f($f)
{
    if (!is_string($f) || !is_callable($f)) {
        return $f;
    }
    [, $max] = num_args($f);
    return fn (...$args) => $f(...array_slice($args, 0, $max));
}

/**
* Recursively check if value is null
* 
* @param mixed $x
* @return bool
*/
function is_null_recursive($x)
{
    if (!is_array($x)) {
        return $x === null;
    }
    foreach ($x as $v) {
        if (!is_null_recursive($v)) {
            return false;
        }
    }
    return true;
}

/**
* Check if value represents a custom type
* 
* @param mixed $x
* @return bool
*/
function is_type($x)
{
    return is_object($x) && method_exists($x, 'is');
}

/**
* Return [min, max] number of args for the given callable
* 
* @param callable $f
* @return array(int,int)
*/
function num_args(callable $f)
{
    if (is_type($f)) return [1, 2];
    $r = is_array($f) ? new \ReflectionMethod(...$f) : new \ReflectionFunction($f);
    return [$r->getNumberOfRequiredParameters(), $r->getNumberOfParameters()];
}

/**
* Return slice of given input
* 
* @param string|array $x
* @param int $start
* @param int $length
* @return string|array
*/
function slice($x, int $start = 0, int $length = null)
{
    if (!$start && !$length) {
        return $x;
    }
    
    $args = [$x, $start];
    if ($length !== null) {
        $args[] = $length;
    }
    
    if (is_array($x)) {
        return array_slice(...$args);
    } else if (is_string($x)) {
        return substr(...$args);
    }
}
