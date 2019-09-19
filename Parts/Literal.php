<?php

namespace Silver\Database\Parts;

use Silver\Database\Query;

class Literal extends Part
{

	private $value;

	public function __construct($value)
	{
		$this->value = $value;
	}

	public static function null(): object
	{
		return new self(null);
	}

	public static function true(): object
	{
		return new self(true);
	}

	public static function false(): object
	{
		return new self(false);
	}

	public static function wild(): object
	{
		return new Raw('*');
	}

	public static function compile(object $q): array
	{
		$value = $q->value;

		if (is_array($value)) {
			return [ '(' . implode(', ', array_map('self::lit', $value)) . ')' ];
		} else {
			return [ self::lit($value) ];
		}
	}

	private static function lit($v): string
	{
		if ($r = static::tryBool($v)) {
			return $r;
		}

		if ($r = static::tryNull($v)) {
			return $r;
		}

		if (is_numeric($v)) {
			return (string) $v;
		}

		return (string) Query::quote($v);
	}

	protected static function tryBool($v): ?string
	{
		if ($v === true) {
			return 't';
		}
		if ($v === false) {
			return 'f';
		}
		return null;
	}

	protected static function tryNull($v): ?string
	{
		if ($v === null) {
			return 'NULL';
		}
		return null;
	}
}
