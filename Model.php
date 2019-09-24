<?php

namespace Silver\Database;

class Model extends QueryObject
{

	public static function query(): object
	{
		return Query::select()
			->from(static::class)
			->setFetchStyle(static::class);
	}

	public static function where($column, string $op = null, $value = null): object
	{
		return static::query()
			->where($column, $op, $value);
	}

	public static function find(int $id): object
	{
		return static::where(static::primaryKey(), $id)
			->first();
	}

	/**
	 * @return mixed
	 */
	public function getFilterable()
	{
		return $this->filterable;
	}

	/**
	 * @return mixed
	 */
	public function getIncludable()
	{
		return $this->includable;
	}

	/**
	 * @return mixed
	 */
	public function getSearchable()
	{
		return $this->searchable;
	}

	/**
	 * @return mixed
	 */
	public function getHidden()
	{
		return $this->hidden;
	}

	/**
	 * @return mixed
	 */
	public function getFillable()
	{
		return $this->fillable;
	}

	/**
	 * @return mixed
	 */
	public function getSelectable()
	{
		return $this->selectable;
	}

	public static function all(): array
	{
		return static::query()->all();
	}

	public static function create(array $data): object
	{
		Query::insert(static::class, $data)->execute();
		return static::find(Query::lastInsertId());
	}

	public function delete(): void
	{
		Query::delete()
			->from(static::class)
			->where(static::primaryKey(), $this->id)
			->execute();
	}

	public function save(): object
	{
		$id = static::primaryKey();

		if (isset($this->$id)) {
			$dirty = $this->dirtyData();

			if (count($dirty) > 0) {
				$q = Query::update(static::class)->where(static::primaryKey(), $this->$id);

				foreach ($dirty as $key => $val) {
					$q->set($key, $val);
				}
				$q->execute();
			}
			return $this;
		} else {
			Query::insert(static::class, $this->data())->execute();
			Query::select()
				->from(static::class)
				->where(static::primaryKey(), Query::lastInsertId())
				->first($this);
			return $this;
		}
	}
}
