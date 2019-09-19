<?php

namespace Silver\Database;


class Relation
{
	private $local_model;
	private $alias;
	private $local_id;
	private $remote_model;
	private $remote_id;
	private $through;
	private $wheres = [];

	public function __construct(object $local)
	{
		$this->localModel($local);
	}

	public function hasOne(object $model, int $local_id, int $remote_id = null): object
	{
		return $this->remoteModel($model)
			->local($local_id)
			->remote($remote_id);
	}

	public function hasMany(object $model, int $remote_id, int $local_id = null): object
	{
		return $this->remoteModel($model)
			->remote($remote_id)
			->local($local_id);
	}

	public function through(int $local_id, string $through_table, int $remote_id): object
	{
		$this->through = [$local_id, $through_table, $remote_id];
		return $this;
	}

	public function local(int $id): object
	{
		$this->local_id = $id;
		return $this;
	}

	public function remote(int $id): object
	{
		$this->remote_id = $id;
		return $this;
	}

	public function localModel(object $model): object
	{
		$this->local_model = $model;
		return $this;
	}

	public function remoteModel(object $model): object
	{
		$this->remote_model = $model;
		return $this;
	}

	public function alias(string $alias): object
	{
		$this->alias = $alias;
		return $this;
	}

	// XXX: unused
	public function getReferences()
	{
		// Incomplete reference exception
		if ($this->through) {
			// return 2 references
		} else {
			// return one reference
		}
	}

	// Output:

	public function getTable(): object
	{
		$rm = $this->remote_model;
		$alias = Parts\Name::ensure($this->alias);
		return new Parts\Table($rm::tableName(), $alias);
	}

	public function getJoinCondition(): object
	{
		$lm = $this->local_model;
		$rm = $this->remote_model;

		$lt =	 Parts\Name::ensure($lm::tableName());
		$lid =	 Parts\Name::ensure($this->local_id ?: $lm::primaryKey());
		$rid =	 Parts\Name::ensure($this->remote_id ?: $rm::primaryKey());
		$alias = Parts\Name::ensure($this->alias);

		return new Parts\JoinCondition([$lt, $lid], '=', [$alias, $rid]);
	}

	public function makeXXXJoin($alias): string
	{


		$rt =	 Parts\Name::ensure($rm::tableName());


		return "LEFT JOIN $rt as $alias ON ($lt.$lid = $alias.$rid)";
	}
}
