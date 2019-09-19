<?php

namespace Silver\Database\Traits;

use Silver\Database\Query;

trait QueryUnion
{
	private $unions = null;

	public function union(object $query): object
	{
		$this->addUnion($query, 'UNION');
		return $this;
	}

	public function unionAll(object $query): object
	{
		$this->addUnion($query, 'UNION ALL');
		return $this;
	}

	private function addUnion(object $query, string $type): void
	{
		if (is_callable($query)) {
			$query = $query();
		}

		if (!($query instanceof Query)) {
			throw new \Exception("Wrong argument for union. '" . \gettype($query) . "' is not 'Query'.");
		}

		$this->unions[] = [$type, $query];
	}

	protected static function compileUnion(object $q): string
	{
		if ($q->unions) {
			$r = '';
			foreach($q->unions as $union) {
				list($key, $query) = $union;
				$r .= " $key " . $query->toSql()[0];
			}
			return $r;
		}
		return '';
	}
}
