<?php

namespace Silver\Database\Source;

use Silver\Database\Source;
use Silver\Database\Parts\Name;
use Silver\Database\Query;

class Model extends Source
{
	private $model;

	protected function __construct(string $model, string $name = null)
	{
		parent::__construct($name ?: $this->shortClassName($model));
		$this->model = $model;
	}

	public function model(): string
	{
		return $this->model;
	}

	public function primary(): string
	{
		$m =  $this->model;
		return $m::primary();
	}

	public function table(): string
	{
		$m = $this->model;
		return $m::tableName();
	}

	public function sourcePart(): Name
	{
		return Name::ensure($this->table());
	}

	public function reference(string $ref, Query $q, string $alias = null, array $args = [])
	{
		$method = 'ref' . ucfirst($ref);
		$m = $this->model;

		if ($alias !== null) {
			array_unshift($args, $alias);
		}

		if (method_exists($m, $method)) {
			return $m::$method($q, ...$args);
		} else {
			throw new \Exception("Missing reference $m::$method.");
		}
	}

	public function relation(string $name, string $alias = null)
	{
		$method = 'rel' . ucfirst($name);
		$m = $this->model;

		if (method_exists($m, $method)) {
			return $m::$method()
				->alias($alias ?: $name);
		} else {
			throw new \Exception("Missing relation $m::$method.");
		}
	}

	private function shortClassName(string $name): string
	{
		$i = strrpos($name, '\\');
		if ($i !== false) {
			$name = substr($name, $i + 1);
		}
		return $name;
	}
}
