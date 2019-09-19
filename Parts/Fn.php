<?php

namespace Silver\Database\Parts;

class Fn extends Part
{

	private $name;
	private $args;

	public static function count(bool $column = null): object
	{
		if ($column === null) {
			$column = Literal::wild();
		}
		return static::ensure(['COUNT', Column::ensure($column)]);
	}

	public static function groupConcat(string $column, string $sep = ','): object
	{
		return static::ensure(
			[
			'GROUP_CONCAT',
			Column::ensure($column),
			Literal::ensure($sep)
			]
		);
	}

	public function __construct(string $name, ...$args)
	{
		$this->name = Raw::ensure($name);
		$this->args = [];
		foreach ($args as $arg) {
			$this->args[] = Literal::ensure($arg);
		}
	}

	public static function __callStatic(string $name, array $args): object
	{
		$args = array_merge([$name], $args);
		return static::ensure($args);
	}

	protected static function mapFn($fn, array $args): array
	{
		return [$fn, $args];
	}

	public static function compile(object $q): array
	{
		list ($name, $args) = static::mapFn($q->name, $q->args);
		return [ $name . '(' . implode(', ', $args) . ')' ];
	}
}
