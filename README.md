# ArrayTransformer

Simple class for transforming a PHP array a myriad of ways

[![Build Status](https://travis-ci.org/perimeter/array-transformer.svg?branch=develop)](https://travis-ci.org/perimeter/array-transformer)

## Installation

Use [composer](http://getcomposer.org) like everyone else

```bash
$ composer require perimeter/array-transformer:dev-develop
```

## Usage

Create an array transformer with your data:

```php
$data = [ 'one' => 'foo', 'two' => [ 'bar', 'baz' ] ];
$transformer = new Perimeter\ArrayTransformer\ArrayTransformer($data);
```

Now, there is no end to the fun you can have!

### Rename

```php
// $data = [ 'A' => 'foo' ]
$transformer->rename('A', 'RENAMED');
// [ 'RENAMED' => 'foo' ]

// $data = [ 'A' => [ 'bar', 'baz' ] ]
$transformer->rename('A/0', 'RENAMED');
// [ 'A' => [ 1 => 'baz' ], 'RENAMED' => 'bar' ]

// $data = [ 'A' => [[ 'B' => 1 ], [ 'B' => 2 ]] ]
$transformer->rename('A/?/B', 'A/?/C');
// [ 'A' => [[ 'C' => 1 ], [ 'C' => '2' ]] ]
```

### Remove

```php
// $data = [ 'A' => 'foo', 'B' => 'bar' ]
$transformer->remove('A');
//  [ 'B' => 'bar' ]

// $data = [ 'A' => [ 'bar', 'baz' ] ]
$transformer->remove('A/?:equals("bar")');
// [ 'A' => [ 'baz' ] ]

// $data = [ 'A' => [ 'B' => 1 ], 'C' => '2' ]
$transformer->remove('A:has(B=2)');
// [ 'C' => '2' ]
```

### Replace

```php
// $data = [ 'A' => 1 ]
$transformer->replace('A', [1=>2]);
//  [ 'A' => 2 ]

// $data = [ 'A' => 'abca' ]
$transformer->replace('A', ['a'=>'xxx']);
// [ 'A' => [ 'xxxbcxxx' ] ]
```

### Translate

```php
// $data = [ [ 'A' => 1 ], [ 'A' => 2 ], [ 'A' => 3 ] ]
$transformer->translate('A/?', [
  1 => 'one',
  2 => 'two',
  3 = >'three'
]);
//  [ [ 'A' => 'one' ], [ 'A' => 'two' ], [ 'A' => 'three' ] ]
```

### Reorder

```php
// $data = [ 'A' => true, 'B' => false ]
$transformer->reorder('A', 'after', 'B');
//  [ 'B' => false, 'A' => true ]

// $data = [ 'A' => true, 'B' => false ]
$transformer->reorder('B', 'before', 'A');
//  [ 'B' => false, 'A' => true ]

// $data = [ 'A' => true, 'B' => false, 'C' => true ]
$transformer->reorder('C', 'first');
//  [ 'C' => true, 'A' => true, 'B' => false ]

// $data = [ 'A' => true, 'B' => false, 'C' => true ]
$transformer->reorder('A', 'last');
//  [ 'B' => false, 'C' => true, 'A' => true ]
```

### Modify

```php
// $data = [ 'A' => 'tEst', 'B' => 'UPPERCASE' ]
$transformer->modify('B', 'strtolower');
//  [ 'A' => 'tEst', 'B' => 'uppercase' ]

// $data = [ 'A' => 'boat', 'B' => [ 'car', 'track' ] ]
$transformer->modify('B', function ($var) { return 'race'.$var; });
//  [ 'A' => 'boat', 'B' => [ 'racecar', 'racetrack' ] ]
```

## Syntax

This class uses the concept of a "path" to index into multi-dimentional arrays.
The wild-card `?` is used to iterate over the entire subarray.

**Examples:**

    A       -->   $array['A']
    A/B     -->   $array['A']['B']
    A/?/B   -->   foreach($array['A'] as $k => $v) $array['A'][$k]['B']
