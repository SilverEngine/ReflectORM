<?php

namespace Silver\Database\Query;

use Silver\Database\Query;
use Silver\Database\Traits\QueryColumns;
use Silver\Database\Traits\QueryFrom;
use Silver\Database\Traits\QueryJoin;
use Silver\Database\Traits\QueryWH;
use Silver\Database\Traits\QueryGroupBy;
use Silver\Database\Traits\QueryHaving;
use Silver\Database\Traits\QueryLimit;
use Silver\Database\Traits\QueryOrder;
use Silver\Database\Traits\QueryUnion;
use Silver\Database\Parts\Literal;

class Select extends Query
{
	use QueryColumns, QueryFrom, QueryJoin, QueryWH, QueryGroupBy, QueryOrder, QueryLimit, QueryUnion;

	public function __construct(array $columns = [])
	{
		$this->setColumns($columns ?: [Literal::wild()]);
	}

	protected static function compile(object $q): array
	{
		$sql = 'SELECT'
			. static::compileColumns($q)
			. static::compileFrom($q)
			. static::compileJoin($q)
			. static::compileWhere($q)
			. static::compileGroupBy($q)
			. static::compileHaving($q)
			. static::compileOrder($q)
			. static::compileLimit($q)
			. static::compileUnion($q);

		return [ $sql ];
	}
}
