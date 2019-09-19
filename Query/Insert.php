<?php

namespace Silver\Database\Query;

use Silver\Database\Query;
use Silver\Database\Parts\Table;
use Silver\Database\Parts\Value;
use Silver\Database\Parts\ColumnList;
use Silver\Database\Source;

class Insert extends Query
{
	private $table;
	private $headers = null;
	private $data = [];

	public function __construct(string $table, array $data = null)
	{
		$source = Source::make($table);
		$this->addSource($source);

		$this->table = Table::ensure($source);
		if ($data !== null) {
			$this->fill($data);
		}
	}

	public function fill(array $data)
	{
		if (!is_array($data)) {
			throw new \Exception("Data must be array.");
		}

		// Multiple data
		if (isset($data[0]) && is_array($data[0])) {
			foreach($data as $d) {
				$this->fill($d);
			}
			return $this;
		}

		if (isset($data[0])) {
			if ($this->headers !== null) {
				$this->headers_exception();
			}
			$this->data[] = $this->data_array($data);
		} else {
			$headers = array_keys($data);
			$data = array_values($data);

			if ($this->headers === null) {
				if (count($this->data)) {
					$this->headers_exception();
				} else {
					$this->headers = $headers;
				}
			} else {
				if ($this->headers != $headers) {
					$this->headers_exception();
				}
			}

			$this->data[] = $this->data_array($data);
		}
		return $this;
	}

	private function data_array(array $data) : array
	{
		return array_map(
			function ($d) {
				return Value::ensure($d);
			}, $data
		);
	}

	private function headers_exception(): void
	{
		throw new \Exception("All data in statement must have same format!");
	}

	protected static function compile(object $q): array
	{
		$sql = 'INSERT INTO ' . $q->table;

		if ($q->headers) {
			$headers = ColumnList::ensure($q->headers);
			$sql .= ' (' . $headers . ')';
		}

		$sql .= ' VALUES ' . implode(
			', ', array_map(
				function ($data) {
					return '(' . implode(', ', $data) . ')';
				}, $q->data
			)
		);

		return [ $sql ];
	}
}
