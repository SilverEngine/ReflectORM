<?php

namespace Silver\Database\Parts;

use Silver\Database\Query;
use Silver\Database\Compiler;
use ReflectionClass;

abstract class Part
{

	use Compiler;

	public function __construct($ignore)
	{
	}

	public static function ensure($args, bool $strict_type = false): object
	{
		if ($args instanceof Part) {
			// Return type must be static::class
			if ($strict_type && !($args instanceof static)) {
				$classof = get_class($args);
				$self = static::class;
				throw new \Exception('Get $classof instead of $self');
			}
			return $args;
		}

		return self::invoke(static::class, $args);
	}

	private static function invoke(string $class, $args): object
	{
		if (!is_array($args)) {
			$args = [$args];
		}
		$ref = new ReflectionClass($class);
		$refMethod = $ref->getConstructor();
		$params = $refMethod->getParameters();
		$count = count($params);

		// Variadic arg
		if ($params && end($params)->isVariadic()) {
			$count = null;
		}

		// To much parameters
		if ($count && ($c=count($args)) > $count) {
			throw new \Exception("To many parameters for class $class. ($c > $count)");
		}

		return $ref->newInstanceArgs($args);
	}

	// Getter & Setter for properties
	public function __call(string $method, array $args): object
	{
		if (strpos($method, "get") === 0) {
			$method = substr($method, 3);
			$method = lcfirst($method);
			if (property_exists($this, $method)) {
				return $this->$method;
			}
		} else if (property_exists($this, $method)) {
			if (count($args) > 1) {
				throw new \Exception("Setters accepted only one value.");
			}

			$arg = count($args) > 0 ? $args[0] : true;
			$this->$method = $arg;
			return $this;
		}

		throw new \Exception('Undefined function: Part::' . $method . '()');
	}
}
