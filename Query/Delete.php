<?php

namespace Silver\Database\Query;

use Silver\Database\Query;
use Silver\Database\Traits\QueryColumns;
use Silver\Database\Traits\QueryFrom;
use Silver\Database\Traits\QueryJoin;
use Silver\Database\Traits\QueryWH;
use Silver\Database\Traits\QueryGroupBy;
use Silver\Database\Traits\QueryOrder;
use Silver\Database\Traits\QueryLimit;

class Delete extends Query
{
	use QueryColumns, QueryFrom, QueryJoin, QueryWH, QueryGroupBy, QueryOrder, QueryLimit;

	public function __construct(array $columns = [])
	{
		$this->setColumns($columns);
	}

	protected static function compile(object $q): array
	{
		$sql = 'DELETE'
			. static::compileColumns($q)
			. static::compileFrom($q)
			. static::compileJoin($q)
			. static::compileWhere($q)
			. static::compileGroupBy($q) // FIXME: remove?
			. static::compileHaving($q) // FIXME: remove
			. static::compileOrder($q)
			. static::compileLimit($q);
		return [ $sql ];
	}
}
