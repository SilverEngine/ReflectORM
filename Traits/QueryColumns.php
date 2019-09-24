<?php

namespace Silver\Database\Traits;

use Silver\Database\Parts\ColumnList;

trait QueryColumns
{
	private $columns;

	private function setColumns(array $columns = []): object
	{
		$this->columns = ColumnList::ensure($columns);
		return $this;
	}

	// Prepare select columns for class
	protected function selectForModel(string $class): void
	{
		$source = $this->getSourceByModel($class);
		if ($source === null) {
			throw new \Exception("Model $class is not used in query.");
		}

		$this->setColumns([[$source->table(), \Silver\Database\Parts\Literal::wild()]]);
	}

	protected static function compileColumns(object $q): string
	{
		return ' ' . $q->columns;
	}
}
