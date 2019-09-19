<?php

namespace Silver\Database\Parts;

class ColumnList extends Part
{
	private $columns;

	public function __construct(...$columns)
	{
		$this->columns = array_map(
			function ($column) {
				return Column::ensure($column);
			}, $columns
		);
	}

	protected static function compile(object $q): array
	{
		return [ implode(', ', $q->columns) ];
	}
}
