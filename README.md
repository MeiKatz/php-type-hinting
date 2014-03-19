PHP type hinting for primitive data types, resources and callables
================

PHP as a big deficit when it comes to passing argument to your own functions: for most of the data types there's no type hinting. Luckly PHP throws an catchable error when you pass the wrong value to a function. We catch this, compare it with the data type we expect and deside whether it is the right or wrong.

So how you do it? It's quite simple – just write the following line before your function and class definitions:
```PHP
TypeHinting::initialize();
```

If there is any other error then you get an InvalidArgumentException by default. You can change this behavior by overwriting the "handle_error" function in a subclass.

Thanks to [daniel.l.wood(at)gmail.com](http://www.php.net/manual/en/language.oop5.typehinting.php#83442) for the inspiration on php.net.

Examples
----------------
```PHP
class Foo {
  public function plus( numeric $a, numeric $b ) {
    return (float) $a + (float) $b;
  }
  
  public function hello( string $name ) {
    return "Hello {$name}!";
  }
}

$foo = new Foo;
$foo->plus( 5, "-7" ); // returns -2.0
$foo->plus( 4.3, "foo" ); // throws InvalidArgumentException for argument 2

$foo->hello( "Peter" ); // returns "Hello Peter!"
$foo->hello( 5 ); // throws InvalidArgumentException
```
