<?php

namespace Silver\Database\Parts;

use Silver\Database\Query;

class Value extends Part
{

	private $value;

	public function __construct(string $value)
	{
		$this->value = $value;
	}

	public static function ensure($args, bool $mustbe = false): object
	{
		if ($args instanceof Part) {
			// Return type must be static::class
			if ($mustbe && !($args instanceof static)) {
				throw new \Exception('Get "' . get_class($args) . '" instead of "' . static::class . '"');
			}
			return $args;
		}

		// Subqueries
		if ($args instanceof Query) {
			return SubQuery::ensure($args);
		}

		// Special treatments for Values
		// Because, value (first argument)
		// can be array or primitive value
		return new self($args);
	}

	protected static function compile(object $q): array
	{
		$value = $q->value;

		if (is_array($value)) {
			$ph = [];
			foreach($value as $v) {
				$ph[] = '?';
				if ($c = Query::current()) {
					$c->bind($v);
				}
			}
			return [ '(' . implode(', ', $ph) . ')' ];
		} else {
			if ($c = Query::current()) {
				$c->bind($value);
			}
			return [ '?' ];
		}
	}
}
