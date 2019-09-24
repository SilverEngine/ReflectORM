<?php

namespace Silver\Database\Parts;

use Silver\Database\Query;

class SubQuery extends Part
{

	private $query;

	public function __construct(Query $q)
	{
		$this->query = $q;
	}

	protected static function compile(object $q): array
	{
		$ret = '(' . $q->query->toSql()[0] . ')';
		if ($c = Query::current()) {
			$c->bind($q->query->getBindings());
		}
		return [ $ret ];
	}
}
