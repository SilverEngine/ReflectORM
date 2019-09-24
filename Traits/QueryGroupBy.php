<?php

namespace Silver\Database\Traits;

use Silver\Database\Parts\Column;
use Silver\Database\Parts\ColumnList;

trait QueryGroupBy
{
	private $groupby = [];

	public function groupBy($column): object
	{
		$this->groupby[] = Column::ensure($column);
		return $this;
	}

	protected static function compileGroupBy(object $q): string
	{
		if (!empty($q->groupby)) {
			return " GROUP BY " . ColumnList::ensure($q->groupby);
		}
		return '';
	}
}
