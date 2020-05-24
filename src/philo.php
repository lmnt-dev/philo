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
const is_countable = 'is_countable';
const is_dir = 'is_dir';
const is_executable = 'is_executable';
const is_file = 'is_file';
const is_finite = 'is_finite';
const is_float = 'is_float';
const is_infinite = 'is_infinite';
const is_int = 'is_int';
const is_iterable = 'is_iterable';
const is_link = 'is_link';
const is_nan = 'is_nan';
const is_null = 'philo\is_null';
const is_numeric = 'is_numeric';
const is_object = 'is_object';
const is_readable = 'is_readable';
const is_resource = 'is_resource';
const is_scalar = 'is_scalar';
const is_string = 'is_string';
const is_uploaded_file = 'is_uploaded_file';
const is_url = 'philo\is_url';
const is_writable = 'is_writable';
const keys = 'array_keys';
const merge = 'array_merge';
const values = 'array_values';

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
* Collections
*----------------------------*/

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
* Match given key against input keys
* 
* @param (callable|string|int)[] $path
* @return callable
*/
function k($path)
{
    return fn ($x, $k = null) => is_callable($path)
        ? $path($k)
        : is($path, $k);
}

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
* @param (string|int)[] $path
* @return callable
*/
function map(callable $f = null, array $path = null)
{
    $f = f($f);
    return reduce(function ($r, $x, $k) use ($f, $path) {
        $r[$k] = $f($x, $path ? array_merge($path, [$k]) : $k);
        return $r;
    });
}

/**
* Pick values via key/index
* 
* @param string[] $keys,...
* @return callable
*/
function pick(string ...$keys)
{
    return filter(k(in($keys)));
}

/**
* Pluck values from collection via key/index
* 
* @param string[] $keys,...
* @return callable
*/
function pluck(string ...$keys)
{
    return map(pick(...$keys));
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
    $f = f($f);
    return kv(fn (array $x) => array_reduce(
        array_keys($x),
        fn ($r, $k) => $f($r, $x[$k], $k),
        $initial
    ));
}

/**
* Return slice of given input
*
* @param int $start
* @param int $length
* @return callable
*/
function slice(int $start = 0, int $length = null)
{
    return function ($x) use ($start, $length) {
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
    };
}

/**
* Interleave multiple arrays
* 
* @param array[] $xs,...
* @return callable
*/
function zip(array ...$xs) {
    return array_merge([], ...array_map(null, ...$xs));
}

/*-----------------------------
* COMPOSITION
*----------------------------*/

/**
* Call functions right to left, passing each result into the preceding function
* 
* Given `compose($f, $g)`, the result will be `$f($g())`
* 
* @param callable[] $fs,...
* @return callable
*/
function compose(callable ...$fs)
{
    return pipe(...array_reverse($fs));
}

/**
* Distribute arguments to multiple callables
*
* @param callable[] $fs,...
* @return callable
*/
function fanout (callable ...$fs) {
    return fn (...$args) => array_map(fn ($f) => f($f)(...$args), $fs);
}

/**
* Call functions left to right, passing each result into the next function
* 
* Given `pipe($f, $g)`, the result will be `$g($f())`
* 
* @param callable[] $fs,...
* @return callable
*/
function pipe(callable ...$fs)
{
    return function ($x = null, $y = null) use ($fs) {
        $x = f(array_shift($fs))($x, $y);
        return array_reduce($fs, fn ($g, $f) => $f($g), $x);
    };
}

/**
* Use input array as arguments to callable
*
* @param callable[] $fs,...
* @return callable
*/
function spread (callable $f) {
    return fn (array $xs) => $f(...$xs);
}

/*-----------------------------
* LOGICAL CONNECTIVES
*----------------------------*/

/**
* Return true if all predicates are true, i.e., logical AND
* 
* @param mixed[] $Ts,...
* @return callable
*/
function all(...$Ts)
{
    $all = function ($Ts, $x, $y) {
        foreach ($Ts as $T) {
            if (!is($T, $x, $y)) {
                return false;
            }
        }
        return true;
    };
    return fn ($x, $k = null) => $all($Ts, $x, $k);
}

/**
* Return true if any predicates are true, i.e., logical OR
* 
* @param mixed[] $Ts,...
* @return callable
*/
function any(...$Ts)
{
    $any = function ($Ts, $x, $k) {
        foreach ($Ts as $T) {
            if (is($T, $x, $k)) {
                return true;
            }
        }
        return false;
    };
    return fn ($x, $k = null) => $any($Ts, $x, $k);
}

/**
* Negate the given predicate, i.e., logical NOT
* 
* @param mixed $T
* @return callable
*/
function not($T)
{
    return fn ($x, $k = null) => !is($T, $x, $k);
}

/*-----------------------------
* MATCHING
*----------------------------*/

/**
* Check if value (and/or optional key) represent an instance of the given type
* 
* @param mixed $T
* @param mixed $x
* @param mixed $k
* @return bool
*/
function is($T, $x, $k = null, $strict = false)
{
    if (is_array($x) && is_array($T)) {
        foreach ($T as $i => $U) {
            if (!isset($x[$i])) {
                if (is($U, null)) continue; // maybe
                return false;
            }
            if (!is($U, $x[$i], $i)) return false;
        }
        if ($strict) {
            foreach (array_keys($x) as $i) {
                if (!isset($T[$i])) {
                    return false;
                }
            }
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
* Match [...[$type, $value]] pairs
* 
* @param array $args,...
* @return callable
*/
function match(...$args)
{
    $f = function ($x, $k = null) use (&$f, $args) {
        foreach (array_chunk($args, 2) as [$T, $value]) {
            if (is($T, $x, $k)) {
                if (!is_callable($value)) {
                    if (is_array($value)) {
                        $f = fn ($v) => is_callable($v) ? f($v)($x, $k) : $v;
                        return map($f)($value);
                    }
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
* @param array $args,...
* @return callable
*/
function rmatch(...$pairs)
{
    return match(...$pairs, ...recurse());
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
* @param mixed[] $in
* @param bool $strict
* @return callable
*/
function in(array $in, $strict = true)
{
    return fn ($x) => in_array($x, $in, $strict);
}

/**
* Return true if input is null or of type $T
* 
* @param mixed $T
* @return callable
*/
function maybe($T) {
    return fn ($x, $k = null) => $x === null || is($T, $x, $k);
}

/**
* Return true only if input contains all required properties of type $T
* 
* @param mixed $T
* @return callable
*/
function strict($T) {
    return fn ($x, $k = null) => is($T, $x, $k, true);
}

/*-----------------------------
* QUANTIFIERS
*----------------------------*/

/**
* Return true if predicate is true for all inputs
* 
* @param mixed $T
* @return callable
*/
function every($T)
{
    return kv(function (array $x) use ($T) {
        foreach ($x as $k => $v) {
            if (!is($T, $v, $k)) return false;
        }
        return true;
    });
}

/**
* Return true if predicate is true for at least one input
* 
* @param mixed $f
* @return callable
*/
function some($T)
{
    return kv(function (array $x) use ($T) {
        foreach ($x as $k => $v) {
            if (is($T, $v, $k)) return true;
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
* @param callable $f
* @return mixed
*/
function f(callable $f)
{
    if (!is_string($f)) {
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
function is_null($x)
{
    if (!is_array($x)) {
        return $x === null;
    }
    foreach ($x as $v) {
        if (!is_null($v)) {
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
* Check if value is a URL
* 
* @param mixed $x
* @return bool
*/
function is_url($x)
{
    return filter_var($x, FILTER_VALIDATE_URL);
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
