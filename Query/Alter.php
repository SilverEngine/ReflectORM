<?php

namespace Silver\Database\Query;

use Silver\Database\Query;
use Silver\Database\Parts\Table;
use Silver\Database\Parts\ColumnDef;
use Silver\Database\Parts\Name;

class Alter extends Query
{

	private $table;
	private $cb;

	private $qs = [];

	public function __construct(string $table, $cb = null)
	{
		$this->table = $table;
		$this->cb = $cb;
	}

	private function addQ(string $type, object $data): object
	{
		$this->qs[] = [$type, $data];
		return $data;
	}

	public function addColumn(string $name, string $type, ...$args): object
	{
		return $this->addQ('add', ColumnDef::ensure(array_merge([$name, $type], $args)));
	}

	public function modifyColumn(string $name, string $type, ...$args): object
	{
		return $this->addQ('modify', ColumnDef::ensure(array_merge([$name, $type], $args)));
	}

	public function changeColumn(string $old_name, string $new_name, string $type, ...$args): object
	{
		$this->addQ(
			'change', [
			Name::ensure($old_name),
			$c = ColumnDef::ensure(array_merge([$new_name, $type], $args))
			]
		);
		return $c;
	}

	public function dropColumn(string $name): object
	{
		return $this->addQ('drop', Name::ensure($name));
	}

	// Execute multiple statements
	public function execute(): object
	{
		$qs = $this->toSql();

		foreach($qs as $sql) {
			if ($this->isDebug()) {
				echo "DEBUG: $sql\n";
			}
			static::exec($sql);
		}

		return $this;
	}

	protected static function compile(object $q): array
	{
		$table = Name::ensure($q->table);
		$q->qs = [];

		if ($cb = $q->cb) {
			$cb($q);
		}

		$qs = [];
		foreach($q->qs as $q) {
			list($type, $data) = $q;
			$fn = 'compile' . ucfirst($type);
			static::$fn(
				$data, function ($sql) use (&$qs, $table) {
					$qs[] = "ALTER TABLE $table " . $sql;
				}
			);
		}

		return $qs;
	}

	protected static function compileAdd(string $c, $add): void
	{
		$add("ADD $c");
	}

	protected static function compileModify(string $c, $add): void
	{
		$add("MODIFY $c");
	}

	protected static function compileChange(string $c, $add): void
	{
		list($old, $newdef) = $c;
		$add("CHANGE $old $newdef");
	}

	protected static function compileDrop(string $c, $add): void
	{
		$add("DROP $c");
	}

	// Forbidden
	public function bind($v) : void
	{
		throw new \Exception("Cannot bind Value to alter query.");
	}
}
