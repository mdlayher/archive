<?php
	// This file demonstrates the functionality found in plinq.  Keep in mind, these functions can all be used
	// on their own, or in tandem via method chaining!

	// Require the plinq class file
	require_once "plinq.php";

	// CREATING A PLINQ COLLECTION
	// Input can be created from...

	// 1) An array, or vector
	$data = array(1, 2, 3);

	// 2) A multidimensional array, or matrix (will be "flattened", and all results returned as normal array)
	// Ex: array(array(1,2),array(3,4)) becomes array(1,2,3,4)
	$data = array(
		array(4, 2, 3),
		array(1, 2, 3),
		array(1, 2, 3),
	);

	// 3) A plinq "constructor" function

	// a) "from": takes a pre-existing collection and stores it
	$p = plinq::from($data);

	// b) "range": creates a collection from x to y, using optional z steps
	$p = plinq::range(0, 9);
	$p = plinq::range(0, 9, 2);

	// c) "fill": creates a collection of length x, filled with identical values y
	$p = plinq::fill(10, 1);
	// Two shortcut functions also exist for "all 0" and "all 1"
	$p = plinq::zeros(10);
	$p = plinq::ones(10);

	// d) "rand": creates a collection of random numbers of length x, optionally between range y and z
	$p = plinq::rand(10);
	$p = plinq::rand(10, 1, 100);

	// e) "merge": creates a collection from any arbitrary combination of scalars, arrays, etc as input
	$p = plinq::merge(1, 2, array(3, 4, 5));
	$p = plinq::merge(array(1, 2, 3, array(4, 5), 6), 7);

	// FUNCTIONS
	// All functions can be chained together, but many functions require numeric input.  Keep this in mind!

	// We will use a range of 1 - 9 for data.
	// When the object is printed directly, it will show a data set snapshot.
	$data = plinq::range(1, 9);
	printf("data: %s\n", $data);
	// data: [0,1,2,3,4,5,6,7,8,9]

	// select() is used to return an output array when operations are complete.
	$data = $data->select();

	// BOOLEAN OPERATIONS
	// Functions which return true or false, based on a condition

	// all(): Are all items in the collection numeric?
	$out = plinq::from($data)->all(function($n)
	{
		return is_numeric($n);
	});
	printf("all(): %s\n", $out == true ? "true" : "false");
	// all(): true

	// any(): Do any of the items in the collection match 3?
	$out = plinq::from($data)->any(function($n)
	{
		return $n == 3;
	});
	printf("any(): %s\n", $out == true ? "true" : "false");
	// any(): true

	// Example: Apply a cube operation only if all input is numeric
	/*
		$d = plinq::range(1, 9);
		if ($d->all(function($n) { return is_numeric($n); }))
		{
			$d = $d->cube()->select();
			// d: [1,8,27,64,125,216,343,512,729]
		}
	*/

	// COMPOSITE FUNCTIONS
	// Functions which accept an anonymous function as their argument.  Among the most flexible available, but
	// more complicated to write.

	// map(): Map an arbitrary anonymous function on to each item in the collection
	$out = plinq::from($data)->map(function($n)
	{
		// Multiply each item by 4
		return 4 * $n;
	});
	printf("map(): %s\n", $out);
	// map(): [4,8,12,16,20,24,28,32,36]

	// single(): Retrieve one and only one item from the collection, which matches an arbitrary anonymous function
	// CAUTION: this will throw an exception if more than one item matches.  Use it carefully, and when you
	// expect one and only one result!
	$out = plinq::from($data)->single(function($n)
	{
		// Retrieve only item equal to 4
		return $n == 4;
	});
	printf("single(): %s\n", $out);
	// single(): 4

	// where(): Retrieve all items from the collection which match an arbitrary anonymous function
	$out = plinq::from($data)->where(function($n)
	{
		// All items with a value > 3
		return $n > 3;
	});
	printf("where(): %s\n", $out);
	// where(): [4,5,6,7,8,9]

	// Example: Map a function onto collection of random numbers, select items which are less than or equal to 5
	/*
		$d = plinq::rand(10, 1, 5)->map(function($n)
		{
			return $n * 2;
		})->where(function($n)
		{
			return $n <= 5;
		})->select();
		// d: [4,2,2,2,2]
	*/

	// PARTITIONING
	// Functions which modify and return a partitioned subset of the original collection

	// even(): Retrieve all items which are even-numbered in the collection
	$out = plinq::from($data)->even();
	printf("even(): %s\n", $out);
	// even(): [2,4,6,8]

	// odd(): Retrieve all items which are odd-numbered in the collection
	$out = plinq::from($data)->odd();
	printf("odd(): %s\n", $out);
	// odd(): [1,3,5,7,9]

	// skip(): Skip x items, then retrieve all remaining items in the collection
	$out = plinq::from($data)->skip(1);
	printf("skip(): %s\n", $out);
	// skip(): [2,3,4,5,6,7,8,9]

	// slice(): Skip x items, then retrieve y items in the collection
	$out = plinq::from($data)->slice(2, 2);
	printf("slice(): %s\n", $out);
	// slice(): [3,4]

	// take(): Retrieve a subset of x items from the collection
	$out = plinq::from($data)->take(2);
	printf("take(): %s\n", $out);
	// take(): [1,2]

	// Example: from a range, skip first 3, take next 3, take only odd numbers
	/*
		$d = plinq::range(0, 9)->skip(3)->take(3)->odd()->select();
		// d: [3,5]
	*/

	// ORDERING
	// Functions which reorder the collection, but do not change its values

	// reverse(): Reverse the order of the collection
	$out = plinq::from($data)->reverse();
	printf("reverse(): %s\n", $out);
	// reverse(): [9,8,7,6,5,4,3,2,1]

	// shuffle(): Randomly shuffle the order of the collection
	$out = plinq::from($data)->shuffle();
	printf("shuffle(): %s\n", $out);
	// shuffle(): [3,2,1,4,6,5,9,8,7]

	// Example: reverse a collection twice, back to its original state
	/*
		$d = plinq::range(1, 9)->reverse()->reverse()->select();
		// d: [1,2,3,4,5,6,7,8,9]
	*/

	// SET OPERATIONS
	// Functions which act upon one or more sets, and return a modified set state

	// distinct(): Retrieve all unique items in the collection
	$out = plinq::from($data)->distinct();
	printf("distinct(): %s\n", $out);
	// distinct(): [1,2,3,4,5,6,7,8,9]

	// except(): Retrieve all items in the collection, except those in the new collection
	$exclude = plinq::ones(9)->select();
	$out = plinq::from($data)->except($exclude);
	printf("except(): %s\n", $out);
	// except(): [2,3,4,5,6,7,8,9]

	// intersect(): Retrieve all items in the collection which also exist in the new collection
	$common = plinq::range(1, 2)->select();
	$out = plinq::from($data)->intersect($common);
	printf("intersect(): %s\n", $out);
	// intersect(): [1,2]

	// union(): Retrieve all items which exist in both the current collection and the new collection
	$new = plinq::rand(3)->select();
	$out = plinq::from($data)->union($new);
	printf("union(): %s\n", $out);
	// union(): [1,2,3,4,5,6,7,8,9,88,471,681]

	// Example: Generate a random set, get distinct numbers, intersect with another random set
	/*
		$int = plinq::rand(10, 1, 10)->select();
		$d = plinq::rand(20, 1, 10)->distinct()->intersect($int)->select();
		// d: [7,4,1,2,9,6,3]
	*/

	// ELEMENT OPERATIONS
	// Functions which return a specific element from the collection

	// element_at(): Retrieve the item at a given index in the collection
	$out = plinq::from($data)->element_at(2);
	printf("element_at(): %s\n", $out);
	// element_at(): 3

	// first(): Retrieve the first item in the collection
	$out = plinq::from($data)->first();
	printf("first(): %s\n", $out);
	// first(): 1

	// last(): Retrieve the last item in the collection
	$out = plinq::from($data)->last();
	printf("last(): %s\n", $out);
	// last(): 9

	// Example: Grab three elements, make a new collection
	/*
		$d = plinq::rand(20, 1, 10)->shuffle();
		$one = $d->first();
		$two = $d->element_at(10);
		$three = $d->last();
		$d = plinq::merge($one, $two, $three)->select()
		// d: [1,2,8]
	*/

	// AGGREGATE OPERATIONS
	// Functions which aggregate data in the collection, and return a scalar result from it

	// average(): Compute the average value of all items in the collection
	$out = plinq::from($data)->average();
	printf("average(): %s\n", $out);
	// average(): 5

	// count(): Retrieve the number of items in the collection
	$out = plinq::from($data)->count();
	printf("count(): %s\n", $out);
	// count(): 9

	// dot(): Compute the dot product of the collection, with another collection of the same length
	$dot = plinq::range(1, 9)->select();
	$out = plinq::from($data)->dot($dot);
	printf("dot(): %s\n", $out);
	// dot(): 285

	// max(): Retrieve the maximum value in the collection
	$out = plinq::from($data)->max();
	printf("max(): %s\n", $out);
	// max(): 9

	// min(): Retrieve the minimum value in the collection
	$out = plinq::from($data)->min();
	printf("min(): %s\n", $out);
	// min(): 1

	// product(): Retrieve the product of all items in the collection
	$out = plinq::from($data)->product();
	printf("product(): %s\n", $out);
	// product(): 362880

	// sum(): Retrieve the sum of all items in the collection
	$out = plinq::from($data)->sum();
	printf("sum(): %s\n", $out);
	// sum(): 45

	// Example: compute several aggregate functions of data set, create new set
	/*
		$d1 = plinq::rand(10, 1, 5);
		$d = plinq::merge($d1->product(), $d1->sum(), $d1->count(), $d1->average())->select();
		// d: [3200,26,10,2.6]
	*/

	// MATHEMATICAL FUNCTIONS
	// Functions which apply a mathematical function over all elements in the collection, modifying the collection

	// crt(): Take cube root (crt(x)) of all items in the collection, retrieve the result
	$out = plinq::from($data)->crt();
	printf("crt(): %s\n", $out);
	// crt(): [1,1.2599210498949,1.4422495703074,1.5874010519682,...]

	// cube(): Cube (n^3) all items in the collection, retrieve the result
	$out = plinq::from($data)->cube();
	printf("cube(): %s\n", $out);
	// cube(): [1,8,27,64,125,216,343,512,729]

	// exp(): Exponentiate (e^x) all items in the collection
	$out = plinq::from($data)->exp();
	printf("exp(): %s\n", $out);
	// exp(): [2.718281828459,7.3890560989307,20.085536923188,...]

	// ln(): Apply the natural log (ln(x)) to all items in the collection
	$out = plinq::from($data)->ln();
	printf("ln(): %s\n", $out);
	// ln(): [0,0.69314718055995,1.0986122886681,1.3862943611199,...]

	// pow(): Apply the power (x^y) function to all items in the collection
	$out = plinq::from($data)->pow(5);
	printf("pow(): %s\n", $out);
	// pow(): [1,32,243,1024,3125,7776,16807,32768,59049]

	// sqrt(): Apply the square root (sqrt(x)) function to all items in the collection
	$out = plinq::from($data)->sqrt();
	printf("sqrt(): %s\n", $out);
	// sqrt(): [1,1.4142135623731,1.7320508075689,2,2.2360679774998,...]

	// square(): Apply the square (x^2) function to all items in the collection
	$out = plinq::from($data)->square();
	printf("square(): %s\n", $out);
	// square(): [1,4,9,16,25,36,49,64,81]

	// Example: method chaining is awesome!
	/*
		$d = plinq::ones(10)->square()->cube()->exp()->ln()->crt()->sqrt()->select();
		// d: [1,1,1,1,1,1,1,1,1,1]
	*/

	// TRIGONOMETRY FUNCTIONS
	// Functions which apply trigonometric functions onto the collection.  If any of these functions return NAN,
	// the input collection falls outside their bounds

	// acos(): Take arccosine (acos(x)) of all items in the collection, retrieve the result
	$out = plinq::from($data)->acos();
	printf("acos(): %s\n", $out);
	// acos(): [0,NAN,...]

	// asin(): Take arcsine (asin(x)) of all items in the collection, retrieve the result
	$out = plinq::from($data)->asin();
	printf("asin(): %s\n", $out);
	// asin(): [1.5707963267949,NAN,...]

	// atan(): Take arctangent (atan(x)) of all items in the collection, retrieve the result
	$out = plinq::from($data)->asin();
	printf("atan(): %s\n", $out);
	// atan(): [1.5707963267949,NAN,...]

	// cos(): Take cosine (cos(x)) of all items in the collection, retrieve the result
	$out = plinq::from($data)->cos();
	printf("cos(): %s\n", $out);
	// cos(): [0.54030230586814,-0.41614683654714,-0.98999249660045,...]

	// sin(): Take sine (sin(x)) of all items in the collection, retrieve the result
	$out = plinq::from($data)->sin();
	printf("sin(): %s\n", $out);
	// sin(): [0.8414709848079,0.90929742682568,0.14112000805987,...]

	// tan(): Take tangent (tan(x)) of all items in the collection, retrieve the result
	$out = plinq::from($data)->asin();
	printf("tan(): %s\n", $out);
	// tan(): [1.5707963267949,NAN,...]

	// Example: compute some trig functions using 0 and pi()
	/*
		$d1 = plinq::from(array(0, pi()));
		$sin = $d1->sin()->select();
		$cos = $d1->cos()->select();
		$tan = $d1->tan()->select();
		$d = plinq::merge($sin, $cos, $tan)->select();
		$d = plinq::merge($sin, $cos, $tan)->select();
		d: [0,1.2246467991474E-16,1,1,1.5574077246549,1.5574077246549]
	*/

	// That's it!  Have any questions or feedback?  Feel free to contact me!
	// - Matt Layher
