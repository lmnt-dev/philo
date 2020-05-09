# ⍎ ρhilo

Functional & friendly

Requires PHP 7.4+

## installation

```
composer require lmnt/philo
```

## code examρles

### `map($f)`

Any callable can be passed to `map` to produce functions that map over arrays:

```php
map('is_string')([1, '2']) // [false, true]
```

Objects can be mapped over in a similar fashion:

```php
map('is_string')((object)['a' => 1, 'b' => '2'])

/* object {
  ["a"] => false
  ["b"] => true
} */
```

### `reduce($f)`

```php
$sum = fn ($x, $y) => $x + $y;

reduce($sum)([1,2,3]) // 6
```

### `filter($f)`

```php
filter('is_string')([1,'2',3,4,'5']) // ['2', '5']
```

### `pluck(...$keys)`

```php
pluck('a', 'z')(['a' => 1, 'b' => 2, 'z' => 3]) // [1, 3]
```

### `every($f)`

```php
every('is_string')([1,'2',3]) // false
```

### `some($f)`

```php
some('is_string')([1,'2',3]); // true
```

### `is($x, $T, $k = null)`

Tests a given value for membership in a set

#### ρrimitives

```php
is(1,'1') // false
is(1, 1)  // true
```

#### classes/interfaces

```php
interface X {}
class Y implements X {}

is(Y::class, X::class) // true
```

#### custom types

```php
$Any = new class {
  function is($x) {
    return true;
  }
};

is(null, $Any) // true
```

### `match(...$pairs)`

Matches `[...[$type, $value]]` pairs

```php
$match = match(
  'is_string', fn ($x) => "$x is a string!",
  'is_int', match(
    gt(40), fn ($x) => "$x > 40"
  )
);

$match('⍎') // "⍎ is a string"
$match(42)  // "42 > 40"
$match(4)   // null
```

### `recurse()`

Extends `match` into tree structures

```php
$kv = fn ($x, $k) => implode('/', (array) $k) . ": $x";

$tree = [
  'a' => 1,
  'b' => [
    'c' => 2,
    'd' => 3,
    'e' => 4
  ]
];

match(
  3, $kv,
  k('a'), $kv,
  k(['b', 'c']), $kv,
  ...recurse()
)($tree);

/* [
  "a" => "a: 1",
  "b" => [
    "c" => "b/c: 2"
    "d" => "b/d: 3"
    "e" => null
  ]
] */
```
