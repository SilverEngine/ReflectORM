<?php

namespace Silver\Database\Source;

use Silver\Database\Source;
use Silver\Database\Query as Q;
use Silver\Database\Parts\SubQuery;

class Query extends Source
{
	private $query;

	protected function __construct(Q $query, string $name)
	{
		parent::__construct($name);
		$this->query = $query;
	}

	public function primary(): string
	{
		return 'id';
	}

	public function table(): Q
	{
		return $this->query;
	}

	public function sourcePart(): SubQuery
	{
		return SubQuery::ensure($this->table());
	}
}
