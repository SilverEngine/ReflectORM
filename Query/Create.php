<?php

namespace Silver\Database\Query;

use Silver\Database\Query;
use Silver\Database\Parts\Table;
use Silver\Database\Parts\ColumnDef;

class Create extends Query
{

	private $table = null;
	private $schema = null;
	// populated by $schema callback
	private $columns = [];

	private $temporary = false;
	private $if_not_exists = false;
	private $autoinc_start = null;

	// Table options
	private $options = [];

	public function __construct(string $table_name, object $schema)
	{
		$this->table = $table_name;
		$this->schema = $schema;
	}

	public function column(string $name, string $type, ...$args): object
	{
		$this->columns[] = $c = ColumnDef::ensure(array_merge([$name, $type], $args));
		return $c;
	}

	public function temporary(bool $isit = true): object
	{
		$this->temporary = $isit;
		return $this;
	}

	public function ifNotExists(bool $yes = true): object
	{
		$this->if_not_exists = $yes;
		return $this;
	}

	public function option(string $key, $value): object
	{
		$key = strtoupper($key);
		if ($value === null) {
			unset($this->options[$key]);
		} else {
			$this->options[$key] = $value;
		}
		return $this;
	}

	public function engine(string $engine): object
	{
		return $this->option('engine', $engine);
	}

	public function charset(string $charset): object
	{
		$this->option('default character set', null);
		return $this->option('character set', $charset);
	}

	public function defaultCharset(string $charset): object
	{
		$this->option('character set', null);
		return $this->option('default character set', $charset);
	}

	// XXX: mysql only
	public function autoincrement(int $first): object
	{
		return $this->option('auto_increment', $first);
	}

	public function comment(string $comment): object
	{
		return $this->option('comment', \Query::quote($comment));
	}

	/* Columns */
	public function boolean(string $name): object
	{
		return $this->column($name, 'boolean');
	}

	public function enum(string $name, ...$enum): object
	{
		return $this->column($name, 'enum', ...$enum);
	}

	public function set(string $name, ...$set): object
	{
		return $this->column($name, 'set', ...$set);
	}

	// Numeric
	public function smallInt(string $name): object
	{
		return $this->column($name, 'smallint');
	}

	public function mediumInt(string $name): object
	{
		return $this->column($name, 'mediumint');
	}

	public function integer(string $name): object
	{
		return $this->column($name, 'integer');
	}

	public function bigInt(string $name): object
	{
		return $this->column($name, 'bigint');
	}

	public function decimal(string $name, int $precision, int $scale): object
	{
		return $this->column($name, 'decimal', $precision, $scale);
	}

	// Text
	public function varchar(string $name, int $size): object
	{
		return $this->column($name, 'varchar', $size);
	}

	public function text(string $name): object
	{
		return $this->column($name, 'text');
	}

	// Time
	public function timestamp(string $name): object
	{
		return $this->column($name, 'timestamp');
	}

	public function time(string $name): object
	{
		return $this->column($name, 'time');
	}

	public function date(string $name): object
	{
		return $this->column($name, 'date');
	}

	public function datetime(string $name): object
	{
		return $this->column($name, 'datetime');
	}

	public function year(string $name): object
	{
		return $this->column($name, 'year');
	}
	/* --- */

	protected static function compile(object $q): array
	{
		$table = Table::ensure($q->table);

		$sql = 'CREATE';
		if ($q->temporary) {
			$sql .= ' TEMPORARY';
		}
		$sql .= ' TABLE';
		if ($q->if_not_exists) {
			$sql .= ' IF NOT EXISTS';
		}
		$sql .= ' ' . $table;

		if ($q->schema instanceof \Parts\Table) {
			$sql .= ' LIKE ' . $q->schema;
		} elseif ($q->schema instanceof \Query\Select) {
			$sql .= ' AS ' . $q->schema->toSql();
		} elseif (is_callable($q->schema)) {

			$schema = $q->schema;
			$q->columns = [];
			$schema($q);

			// Columns
			$parts = [];
			foreach ($q->columns as $c) {
				$parts[] = $c->toSql()[0];
			}
			$sql .= ' (' . implode(', ', $parts) . ')';

			if (count($q->options) > 0) {
				foreach($q->options as $k=>$v) {
					$sql .= ' ' . $k . '=' . $v;
				}
			}
		} else {
			throw new \Exception("Unknown schema type.");
		}

		return [ $sql ];
	}
}
