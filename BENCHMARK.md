Benchmark
================

How I measured the times
----------------

I did each benchmark test ten times and calculated the average time. Benchmarks were done on the command line on a Fedora 20 system with 6 GiB RAM and a i5-33170 CPU processor @ 1.70 GHz with 4 cores. I used the following script:
```PHP
TypeHinting::initialize();

function foobar_boolean( boolean $value ) {} # or any other data type

$i = 0;
$start_time = microtime( true );

while ( $i < 1000 ) {
  ++$i;
  
  # exception routine is needed because elsewise
  # the script would stop when the first exception occurs
  try {
    foobar_boolean( true ); # or any other function and data type
  } catch ( Exception $e ) {}
}

$end_time = microtime( true );
$time     = $end_time - $start_time;

echo 'time used: ', $time;
```

Benchmark results
----------------

| expected type | given type | time for 1000 calls |  
| ------------- | ---------- | -------------------:|
| boolean       | boolean    |    6150 µsec        |
| boolean       | integer    | __17546 µsec__      |
| integer       | integer    |    6324 µsec        |
| integer       | boolean    | __17783 µsec__      |
| double        | double     |    6852 µsec        |
| double        | boolean    | __18228 µsec__      |
| string        | string     |    6707 µsec        |
| string        | boolean    | __20813 µsec__      |
| resource      | resource   |    6460 µsec        |
| resource      | boolean    | __18907 µsec__      |
| number        | integer    |    6975 µsec        |
| number        | double     |    7211 µsec        |
| number        | boolean    | __18823 µsec__      |
| scalar        | boolean    |    6703 µsec        |
| scalar        | integer    |    6909 µsec        |
| scalar        | double     |    7054 µsec        |
| scalar        | string     |    7019 µsec        |
| scalar        | resource   | __18611 µsec__      |
| mixed         | boolean    |    6541 µsec        |
| numeric       | integer    |   13490 µsec        |
| numeric       | double     |   13713 µsec        |
| numeric       | string     |   14046 µsec        |
| numeric       | boolean    | __24074 µsec__      |

Conclusion
----------------
* there is no differenc between data types as long (in the same case)
* boolean, integer, double, string, resource and mixed need almost the same time
* number and scalar need a little longer
* numeric needs nearly twice the time
* calls need up to three times if you pass the wrong data type

You should bear in mind that in the most cases you will stop if there is an error. Also you will not put an exception handler around every function call. So the times might be some lower than my measured times.
