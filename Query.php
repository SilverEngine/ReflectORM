<?php

namespace Silver\Database;

use Silver\Database\Query\Drop;
use Silver\Database\Parts\Fn;
use Silver\Database\Parts\Column;

abstract class Query extends Db
{
	private $bindings = [];
	private $sources = [];

	use Compiler;

	/**
	 * @param array ...$columns
	 * @return mixed
	 */
	public static function select(...$columns): object
	{
		return self::instance('select', [$columns]);
	}

	/**
	 * @param string $column
	 * @return mixed
	 */
	public static function count(string $column = 'count'): object
	{
		return self::select(
			Column::ensure(
				[
				null,
				Fn::count(),
				$column
				]
			)
		);
	}

	/**
	 * @param array ...$columns
	 * @return mixed
	 */
	public static function delete(...$columns): object
	{
		return self::instance('delete', [$columns]);
	}

	/**
	 * @param $table
	 * @param array $updates
	 * @return mixed
	 */
	public static function update(string $table, array $updates = []): object
	{
		return self::instance('update', [$table, $updates]);
	}

	/**
	 * @param $table
	 * @param null	$data
	 * @return mixed
	 */
	public static function insert(string $table, array $data = null): object
	{
		return self::instance('insert', [$table, $data]);
	}

	/**
	 * @param $table
	 * @param $cb
	 * @return mixed
	 */
	public static function create(string $table, $cb): object
	{
		return self::instance('create', [$table, $cb]);
	}

	/**
	 * @param $table
	 * @return mixed
	 */
	public static function drop(string $table): object
	{
		return self::instance('drop', [$table]);
	}

	/**
	 * @param $table
	 * @param null	$cb
	 * @return mixed
	 */
	public static function alter(string $table, $cb = null): object
	{
		return self::instance('alter', [$table, $cb]);
	}

	/**
	 * @param $type
	 * @param array $args
	 * @return mixed
	 */
	protected static function instance(string $type, array $args = []): object
	{
		$class = 'Silver\\Database\\Query\\' . ucfirst($type);
		return new $class(...$args);
	}

	/**
	 * @param $value
	 */
	public function bind($value): void
	{
		if (is_array($value)) {
			$this->bindings = array_merge($this->bindings, $value);
		} else {
			$this->bindings[] = $value;
		}
	}

	/**
	 * @return array
	 */
	public function getBindings(): array
	{
		return $this->bindings;
	}

	/**
	 *
	 */
	public function clearBindings(): void
	{
		$this->bindings = [];
	}

	public function addSource(object $source): void
	{
		$this->sources[$source->name()] = $source;
	}

	public function getSource(string $name): ?object
	{
		if (isset($this->sources[$name])) {
			return $this->sources[$name];
		}
		return null;
	}

	public function getSourceByModel(object $class): ?object
	{
		foreach($this->sources as $source) {
			if ($source instanceof \Silver\Database\Source\Model) {
				if ($source->model() == $class) {
					return $source;
				}
			}
		}
		return null;
	}
}
