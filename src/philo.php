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

use iter;

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
const combine = 'iter\zipKeyValue';
const keys = 'iter\keys';
const range = 'iter\range';
const to_array = 'philo\to_array';
const values = 'iter\values';

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

/**---------------------------
* * CREATION *
*----------------------------*/

/**
* Construct an instance of the given type
* 
* @psalm-template T
* @param T $T
* @param mixed $x
* @param mixed $k
* @param bool $strict
* @return Left|Right
*/
function create($T, $x, $k = null, $strict = false)
{
    if (is_array($x) && is_array($T)) {
        $r = [];
        $left = false;
        /** @var T $U */
        foreach ($T as $i => $U) {
            if (!isset($x[$i])) {
                // maybe
                if (is($U, null)) continue;
                $left = true;
            } else {
                $r[$i] = create($U, $x[$i], $i);
                if (is_left($r[$i])) $left = true;
            }
        }
        if ($strict) {
            foreach (array_keys($x) as $i) {
                if (!isset($T[$i])) {
                    $left = true;
                    $r[$i] = left($x[$i]);
                }
            }
        }
        return $left ? left($r) : right($r);
    } else if (is_type_object($T)) {
        if (!$T->is($x)) return left($x);
    } else if (!is_callable($T)) {
        if (is_string($T)) {
            if (class_exists($T) || interface_exists($T)) {
                if (!$x instanceof $T) return left($x);
            } else if ($x !== $T) {
                return left($x);
            }
        } else if ($x !== $T) {
            return left($x);
        }
    } else {
        /** @var bool|Left|Right $v */
        $v = f($T)($x, $k);
        return is_left($v) ? left($x) : right($x);
    }
    return right($x);
}

/**
 * Left typically represents an invalid state
 * @property mixed $value
 */
class Left
{
    /**
     * @readonly
     * */
    public $value;

    /**
    * @param mixed $x
    */
    function __construct($x)
    {
        $this->value = $x;
    }
}

/**
 * Right typically represents a valid state
 * @property mixed $value
 */
class Right
{
    /**
     * @readonly
     * */
    public $value;

    /**
    * @param mixed $x
    */
    function __construct($x)
    {
        $this->value = $x;
    }
}

/**
* @param mixed $x
* @return Left
*/
function left($x)
{
    return new Left($x);
}

/**
* @param mixed $x
* @return Right
*/
function right($x)
{
    return new Right($x);
}

/**
 * Unwrap the left side of a result
 * 
 * @param array|Left|Right $x
 * @return mixed
 */
function lval($x)
{
    if (is_array($x)) {
        return array_map(__FUNCTION__, $x);
    }
    if ($x instanceof Left) {
        return lval($x->value);
    }
    if ($x instanceof Right) {
        return is_array($x->value) ? array_map(fn () => null, $x->value ?? $x) : null;
    }
    return $x;
}

/**
 * Unwrap the right side of a result
 * 
 * @param array|Left|Right $x
 * @return mixed
 */
function rval($x)
{
    if (is_array($x)) {
        return array_map(__FUNCTION__, $x);
    }
    if ($x instanceof Left) {
        return is_array($x->value) ? rval($x->value) : null;
    }
    if ($x instanceof Right) {
        return rval($x->value);
    }
    return $x;
}

/**
* @param bool|Left|Right $x
* @return bool
*/
function is_left($x)
{
    return $x === false || $x instanceof Left;
}

/**
* @param bool|Left|Right $x
* @return bool
*/
function is_right($x)
{
    return $x === true || $x instanceof Right;
}

/**
* Return true if input is null or type $T
* 
* @psalm-template T
* @param T $T
* @return callable(mixed, mixed=) : bool
*/
function maybe($T) {
    return fn ($x, $k = null) => $x === null || is($T, $x, $k);
}

/**
* Return type $T if input contains all required properties
* 
* @psalm-template T
* @param T $T
* @return callable(mixed, mixed=) : bool
*/
function strict($T) {
    return fn ($x, $k = null) => create($T, $x, $k, true);
}

/**---------------------------
* * COLLECTIONS *
*----------------------------*/

/**
* Filter inputs using the given predicate
* 
* @param callable $f
* @return callable
*/
function filter(callable $f)
{
    return function (iterable $x) use ($f) {
        $f = f($f);
        foreach ($x as $k => $v) {
            if ($f($v, $k)) {
                yield $k => $v;
            }
        }
    };
}

/**
* Match given key against input keys
* 
* @param callable|(string|int)[] $path
* @return callable(mixed, mixed=) : mixed
*/
function k($path)
{
    return fn ($x, $k = null) => is_callable($path)
        ? $path($k)
        : is($path, $k);
}

/**
* Map inputs to outputs via the given function
* 
* @param callable $f
* @param (string|int)[] $path
* @return callable
*/
function map(callable $f, array $path = null)
{
    return fn ($x) => !is_iterable($x) ? null : (
        function ($x) use ($f, $path) {
            $f = f($f);
            foreach ($x as $k => $v) {
                if ($path) $k = array_merge($path, [$k]);
                yield $k => $f($v, $k);
            }
        }
    )($x);
}

/**
* Pick values via key/index
* 
* @param string ...$keys
* @return callable
*/
function pick(string ...$keys)
{
    return filter(k(in($keys)));
}

/**
* Pluck values from collection via key/index
* 
* @param string ...$keys
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
* @return callable(iterable) : mixed
*/
function reduce(callable $f, $initial = null)
{
    return fn (iterable $x) => iter\reduce(f($f), $x, $initial);
}

/**
* Return slice of given input
*
* @param int $start
* @param int $length
* @return callable(mixed) : mixed
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
        } else if (is_iterable($x)) {
            return iter\slice(...$args);
        } else if (is_string($x)) {
            return substr(...$args);
        }
    };
}

/**
* Convert iterable results to array
* 
* @param callable $f
* @param bool $with_keys
* @return callable(mixed, mixed=) : array
*/
function to_array(callable $f, $with_keys = false)
{
    return fn ($x, $k = null) => iter\recurse(
        $with_keys ? 'iter\toArrayWithKeys' : 'iter\toArray',
        f($f)($x, $k)
    );
}

/**---------------------------
* * COMPOSITION *
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
* @param callable ...$fs
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
* @param callable ...$fs
* @return callable
*/
function pipe(callable ...$fs)
{
    return function ($x = null, $k = null) use ($fs) {
        $x = f(array_shift($fs))($x, $k);
        return array_reduce($fs, fn ($g, $f) => $f($g), $x);
    };
}

/**
* Use input array as arguments to callable
*
* @param callable ...$fs
* @return callable
*/
function spread (callable $f) {
    return fn (array $xs) => $f(...$xs);
}

/**---------------------------
* * LOGICAL CONNECTIVES *
*----------------------------*/

/**
* Logical AND / Intersection Type
* 
* @psalm-template T
* @param T ...$Ts
* @return callable(mixed, mixed=) : (Left|Right)
*/
function all(...$Ts)
{
    return function ($x, $k = null) use ($Ts) {
        foreach ($Ts as $T) {
            $v = create($T, $x, $k);
            if (is_left($v)) {
                return left($v);
            }
        }
        return right($x);
    };
}

/**
* Logical OR / Union Type
* 
* @psalm-template T
* @param T ...$Ts
* @return callable(mixed, mixed=) : (Left|Right)
*/
function any(...$Ts)
{
    return function ($x, $k = null) use ($Ts) {
        foreach ($Ts as $T) {
            $v = create($T, $x, $k);
            if (is_right($v)) {
                return right($v);
            }
        }
        return left($x);
    };
}

/**---------------------------
* * MATCHING *
*----------------------------*/

/**
* Check if value (and optional key) represent an instance of the given type
* 
* @psalm-template T of callable(mixed, mixed=) : (bool|Left|Right)
* @param T|T[] $T
* @param null|int|string|array $x
* @param string|string[] $k
* @param bool $strict
* @return bool
*/
function is($T, $x, $k = null, $strict = false)
{
    return is_right(create($T, $x, $k, $strict));
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
        is_iterable, $recurse,
        is_object, $recurse
    ];
}

/**
* Full recursive match of [...[$type, $value]] pairs
* 
* @param array ...$pairs
* @return callable
*/
function rmatch(...$pairs)
{
    return match(...$pairs, ...recurse());
}

/**---------------------------
* * PREDICATES *
*----------------------------*/

/**
* Return true if argument is strictly equal to input
* 
* @param mixed $y
* @return callable(mixed) : bool
*/
function eq($y)
{
    return fn ($x) => $x === $y;
}

/**
* Return true if argument is greater than input
* 
* @param mixed $y
* @return callable(mixed) : bool
*/
function gt($y)
{
    return fn ($x) => $x > $y;
}

/**
* Return true if argument is greater than or equal to input
* 
* @param mixed $y
* @return callable(mixed) : bool
*/
function gte($y)
{
    return fn ($x) => $x >= $y;
}

/**
* Return true if argument is less than input
* 
* @param mixed $y
* @return callable(mixed) : bool
*/
function lt($y)
{
    return fn ($x) => $x < $y;
}

/**
* Return true if argument is less than or equal to input
* 
* @param mixed $y
* @return callable(mixed) : bool
*/
function lte($y)
{
    return fn ($x) => $x <= $y;
}

/**
* Return true if input is found in array
* 
* @param (int|string)[] $in
* @param bool $strict
* @return callable(mixed) : bool
*/
function in(array $in, $strict = true)
{
    return fn ($x) => in_array($x, $in, $strict);
}

/**---------------------------
* * QUANTIFIERS *
*----------------------------*/

/**
* Return true if predicate is true for all inputs
* 
* @param mixed $T
* @return callable
*/
function every($T)
{
    return function ($x) use ($T) {
        if ($x === null) {
            return is($T, null) ? right($x) : left($x);
        }
        $r = [];
        $left = false;
        foreach ($x as $k => $v) {
            $r[$k] = create($T, $v, $k);
            if (is_left($r[$k])) $left = true;
        }
        return $left ? left($r) : right($r);
    };
}

/**
* Return true if predicate is true for at least one input
* 
* @param mixed $f
* @return callable
*/
function some($T)
{
    return function ($x) use ($T) {
        foreach ($x as $k => $v) {
            if (is($T, $v, $k)) return right($x);
        }
        return left($x);
    };
}

/**--------------------------
* * STREAMING *
*----------------------------*/

/**
* Stream JSON from input to output
* 
* @param callable $f
* @param resource $input
* @param resource $output
* @return void
*/
function stream_json(callable $f, $input = STDIN, $output = STDOUT) {
    (new \JsonCollectionParser\Parser())->parse(
        $input, fn (array $x) => fwrite($output, json_encode($f($x)) . "\n" )
    );
}

/**---------------------------
* * SUPPORT *
*----------------------------*/

/**
* Prevents strict mode errors by truncating args where necessary
* 
* Only applies to callable strings, other values will be returned as is
* 
* @param callable $f
* @return callable
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
    if (!is_iterable($x)) {
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
function is_type_object($x)
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
    return filter_var($x, FILTER_VALIDATE_URL) !== false;
}

/**
* Return [min, max] number of args for the given callable
* 
* @return array<int,int>
*/
function num_args(callable $f)
{
    if (is_type_object($f)) return [1, 2];
    $r = is_array($f) ? new \ReflectionMethod(...$f) : new \ReflectionFunction($f);
    return [$r->getNumberOfRequiredParameters(), $r->getNumberOfParameters()];
}
