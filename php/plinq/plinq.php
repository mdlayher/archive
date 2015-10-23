<?php
	// plinq.php - Matt Layher, 7/23/2013
	// PHP class for C# LINQ-style and Python Numpy-style manipulation of PHP arrays

	// PHP 5.4 and below don't have array_column(), so add the userspace library for it
	require_once "array_column.php";

	class plinq
	{
		// Data array, manipulated by this class
		private $data = array();

		// Create instance and store input
		private function __construct(array $input)
		{
			// Flatten input multidimensional arrays, for easier manipulation
			if (self::is_multi($input))
			{
				$this->data = self::array_flatten($input);
			}
			// Store standard arrays
			else
			{
				$this->data = $input;
			}
		}

		// Print a snapshot of the data array (in PHP 5.4 style, looks nicer here)
		public function __toString()
		{
			$out = "[";
			foreach ($this->data as $a)
			{
				if (is_array($a))
				{
					$out .= "[";
					foreach ($a as $i)
					{
						$out .= $i . ",";
					}
					$out = rtrim($out, ",");
					$out .= "],";
				}
				else
				{
					$out .= $a . ",";
				}
			}

			$out = rtrim($out, ",");
			$out .= "]";

			return $out;
		}

		// Generate a data set dynamically using array_fill()
		public static function fill($num, $value)
		{
			if (!is_int($num) || $num < 0 || !is_int($value))
			{
				throw new Exception("plinq::fill(): expects positive integer num and integer value");
				return null;
			}

			return new self(array_fill(0, $num, $value));
		}

		// Entry point for plinq data set, declared static for immediate method chaining capabilities
		public static function from(array $input)
		{
			return new self($input);
		}

		// Create a data set from arbitrary scalars and arrays
		public static function merge()
		{
			return new self(func_get_args());
		}

		// Generate a data set of random numbers, with length given in parameter
		public static function rand($length, $min = 1, $max = 1000)
		{
			// Validate positive integer length
			if (!is_int($length) || $length < 1)
			{
				throw new Exception("plinq::rand(): expects positive integer length");
				return null;
			}

			// Generate random numbers to fill array
			$d = array();
			for ($i = 0; $i < $length; $i++)
			{
				$d[] = mt_rand($min, $max);
			}

			return new self($d);
		}

		// Generate a data set over range of numbers
		public static function range($start, $end, $step = 1)
		{
			// Validate that all values are positive integers
			if (!is_int($start) || $start < 0 || !is_int($end) || $end < 0 || !is_int($step) || $step < 0)
			{
				throw new Exception("plinq::range(): expects positive integer start, end, and step values");
				return null;
			}

			return new self(range($start, $end, $step));
		}

		// Shortcut: generate a data set of all ones
		public static function ones($length)
		{
			return self::fill($length, 1);
		}

		// Shortcut: generate a data set of all zeros
		public static function zeros($length)
		{
			return self::fill($length, 0);
		}

		// Apply the acos function over the entire data set
		public function acos()
		{
			return $this->map_math("acos");
		}

		// Apply the asin function over the entire data set
		public function asin()
		{
			return $this->map_math("asin");
		}

		// Apply the atan function over the entire data set
		public function atan()
		{
			return $this->map_math("atan");
		}

		// Do all of the elements in data array match the provided function?
		public function all(callable $f)
		{
			// Attempt to select items matching function
			$match = array_filter($this->data, $f);

			// If all elements match, return true
			return $this->data == $match ? true : false;
		}

		// Do any of the elements in data array match the provided function?
		public function any(callable $f)
		{
			// Attempt to select items matching function
			if (array_filter($this->data, $f))
			{
				return true;
			}

			// No result
			return false;
		}

		// Calculate the average of all elements in data set
		public function average()
		{
			// Ensure all elements are valid numbers
			if ($this->all(function($n) { return is_numeric($n); }))
			{
				return array_sum($this->data) / count($this->data);
			}

			// If invalid numbers, throw exception
			throw new Exception("plinq->average(): expects completely numeric data set");

			return null;
		}

		// Apply the cos function over the entire data set
		public function cos()
		{
			return $this->map_math("cos");
		}

		// Return count of number of items in data set
		public function count()
		{
			return count($this->data);
		}

		// Shortcut: Take cube root of each number in data set
		public function crt()
		{
			return $this->pow(1/3);
		}

		// Shortcut: Cube each number in data set
		public function cube()
		{
			return $this->pow(3);
		}

		// Remove any duplicate elements from data set
		public function distinct()
		{
			$this->data = array_unique($this->data);

			return $this;
		}

		// Compute dot product of data set and an input data set
		public function dot(array $a)
		{
			// Flatten multidimensional array by column, for dot product
			if (self::is_multi($a))
			{
				$a = self::array_flatten_column($a);
			}

			// Ensure same length
			if (count($this->data) !== count($a))
			{
				throw new Exception("plinq->dot(): expects data sets of same length, current lengths: " . count($this->data) . " " . count($a));
				return null;
			}

			// Ensure all elements are valid numbers
			if (!$this->all(function($n) { return is_numeric($n); }))
			{
				throw new Exception("plinq->dot(): expects completely numeric data set");
			}

			// Compute dot product
			return array_sum(array_map(function($x, $y)
			{
				return $x * $y;
			}, $this->data, $a));
		}

		// Grab element at specified index
		public function element_at($i)
		{
			// Verify i within bounds
			if (is_int($i) && count($this->data) > $i)
			{
				return $this->data[$i];
			}

			// If invalid numbers, throw exception
			throw new Exception("plinq->element_at(): expects positive integer index, within bounds of data set");

			return null;
		}

		// Filters all even elements in a numeric set
		public function even()
		{
			// Ensure all elements are valid numbers
			if (!$this->all(function($n) { return is_numeric($n); }))
			{
				throw new Exception("plinq->even(): expects completely numeric data set");
				return null;
			}

			// Filter out all even numbers in set
			return $this->where(function($n) { return $n % 2 == 0; });
		}

		// Remove any elements from data set which appear in input set
		public function except(array $a)
		{
			$this->data = array_diff($this->data, $a);

			return $this;
		}

		// Apply the exponentiate function over the entire data set
		public function exp()
		{
			return $this->map_math("exp");
		}

		// Get first element from result set
		public function first()
		{
			// Return first element
			if (count($this->data) > 0)
			{
				return reset($this->data);
			}

			throw new Exception("plinq->first(): cannot get first element from empty data set");

			return null;
		}

		// Only retain elements in data set which are also present in input set
		public function intersect(array $a)
		{
			$this->data = array_intersect($this->data, $a);

			return $this;
		}

		// Get last element from result set
		public function last()
		{
			// Return last element
			if (end($this->data))
			{
				return end($this->data);
			}

			throw new Exception("plinq->last(): could not reset pointer to last element in data set");

			return null;
		}

		// Apply the natural log function over the entire data set
		public function ln()
		{
			return $this->map_math("log");
		}

		// Apply a user-defined function over the entire data set
		public function map(callable $f)
		{
			$this->data = array_map($f, $this->data);

			return $this;
		}

		// Return maximum value present in data set
		public function max()
		{
			// Ensure all elements are valid numbers
			if (!$this->all(function($n) { return is_numeric($n); }))
			{
				throw new Exception("plinq->max(): expects completely numeric data set");
				return null;
			}

			// Filter out max number in set
			return max($this->data);
		}

		// Return minimum value present in data set
		public function min()
		{
			// Ensure all elements are valid numbers
			if (!$this->all(function($n) { return is_numeric($n); }))
			{
				throw new Exception("plinq->min(): expects completely numeric data set");
				return null;
			}

			// Filter out min number in set
			return min($this->data);
		}

		// Filters all odd elements in a numeric set
		public function odd()
		{
			// Ensure all elements are valid numbers
			if (!$this->all(function($n) { return is_numeric($n); }))
			{
				throw new Exception("plinq->odd(): expects completely numeric data set");
				return null;
			}

			// Filter out all odd numbers in set
			return $this->where(function($n) { return $n % 2 != 0; });
		}

		// Apply power to each number in data set
		public function pow($p)
		{
			// Ensure all elements are valid numbers, and that p is a valid number
			if (!$this->all(function($n) { return is_numeric($n); }) || !is_numeric($p))
			{
				throw new Exception("plinq->pow(): expects completely numeric data set and power");
				return null;
			}

			// Apply pow function to all elements in data set
			return $this->map(function($n) use ($p)
			{
				return pow($n, $p);
			});
		}

		// Calculate the product of all elements in data set
		public function product()
		{
			// Ensure all elements are valid numbers
			if ($this->all(function($n) { return is_numeric($n); }))
			{
				return array_product($this->data);
			}

			throw new Exception("plinq->product(): expects completely numeric data set");

			return null;
		}

		// Reverse the data set, in-place
		public function reverse()
		{
			$this->data = array_reverse($this->data);

			return $this;
		}

		// Return the projected data set
		// TODO: Return true projection with fields in future
		public function select()
		{
			return $this->data;
		}

		// Shuffles the elements in data set
		public function shuffle()
		{
			shuffle($this->data);

			return $this;
		}

		// Apply the sin function over the entire data set
		public function sin()
		{
			return $this->map_math("sin");
		}

		// Return one item, enforcing that it is the only item in data set.  Throw exception on failure.
		public function single(callable $f = null)
		{
			// If a function is set, check for matches
			if (isset($f) && $this->any($f))
			{
				// Apply function to data set
				$this->where($f);
			}

			// Return one and only one result
			if (count($this->data) === 1)
			{
				return current($this->data);
			}

			// If more or less than one result, throw exception
			throw new Exception("plinq->single(): expected 1 result, but got: " . count($this->data));

			return null;
		}

		// Skips first i items in data set, setting the remainder as the new data set
		public function skip($i)
		{
			// Ensure i is within bounds of data
			if (!is_int($i) || $i >= count($this->data))
			{
				throw new Exception("plinq->skip(): expects positive integer index within bounds of data set");
				return null;
			}

			// Skip i items, take the rest
			$this->data = array_slice($this->data, $i);

			return $this;
		}

		// Extract a slice of the data set, rather than the whole set
		public function slice($offset, $length)
		{
			// Validate positive integers
			if (!is_int($offset) || $offset < 0 || !is_int($length) || $length < 0)
			{
				throw new Exception("plinq->slice(): expects positive integer offset and length");
				return null;
			}

			$this->data = array_slice($this->data, $offset, $length);

			return $this;
		}

		// Shortcut: Apply square root to each number in data set
		public function sqrt()
		{
			return $this->pow(1/2);
		}

		// Shortcut: Square each number in data set
		public function square()
		{
			return $this->pow(2);
		}

		// Calculate the sum of all elements in data set
		public function sum()
		{
			// Ensure all elements are valid numbers
			if ($this->all(function($n) { return is_numeric($n); }))
			{
				return array_sum($this->data);
			}

			throw new Exception("plinq->sum(): expects completely numeric data set");

			return null;
		}

		// Takes i items from data set, setting the items as the new data set
		public function take($i)
		{
			// Ensure i is within bounds of data
			if (!is_int($i) || $i >= count($this->data))
			{
				throw new Exception("plinq->take(): expects positive integer index within bounds of data set");
				return null;
			}

			// Take i items
			$this->data = array_slice($this->data, 0, $i);

			return $this;
		}

		// Apply the tan function over the entire data set
		public function tan()
		{
			return $this->map_math("tan");
		}

		// Compute union of data set and input data set, removing duplicates
		public function union(array $a)
		{
			$this->data = array_unique(array_merge($this->data, $a));

			return $this;
		}

		// Apply a function to all items in data set, take matching results
		public function where(callable $f)
		{
			// Apply function
			$filtered = array_filter($this->data, $f);

			// If array is not associative, reset the keys
			if (!self::is_assoc($filtered))
			{
				$filtered = array_values($filtered);
			}

			// Apply new data set
			$this->data = $filtered;

			return $this;
		}

		// Validates data, and then maps a PHP math function onto the data set
		private function map_math($f)
		{
			// Ensure all elements are valid numbers
			if (!$this->all(function($n) { return is_numeric($n); }))
			{
				throw new Exception("plinq->map_math(" . $f . "): expects completely numeric data set");
				return null;
			}

			// Apply the math function
			return $this->map(function($n) use ($f)
			{
				return $f($n);
			});
		}

		// Flattens a multidimensional array by horizontal rows
		// Thanks: http://stackoverflow.com/questions/1319903/how-to-flatten-a-multidimensional-array
		private static function array_flatten(array $array)
		{
			$return = array();
			array_walk_recursive($array, function($a) use (&$return)
			{
				$return[] = $a;
			});
			return $return;
		}

		// Flattens a multidimensional array by vertical rows
		private static function array_flatten_column(array $array)
		{
			// Iterate sub-arrays
			$return = array();
			for ($i = 0; $i < count($array); $i++)
			{
				// Get column of values, merge it to return
				$return = array_merge($return, array_column($array, $i));
			}

			return $return;
		}

		// Determines if an array is associative or not
		// Thanks: http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
		private static function is_assoc(array $a)
		{
			return array_keys($a) === range(0, count($a) - 1);
		}

		// Determines if an array is multidimensional or not
		// Thanks: http://stackoverflow.com/questions/145337/checking-if-array-is-multidimensional-or-not
		private static function is_multi(array $a)
		{
			return count(array_filter($a, "is_array")) > 0 ? true: false;
		}
	}
