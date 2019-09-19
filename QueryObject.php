<?php

namespace Silver\Database;

class QueryObject
{

	protected static $_table = null;
	protected static $_primary = null;
	private $props = [];
	private $dirty = [];

	public function __construct()
	{
		$this->dirty = [];
	}

	public static function tableName(): string
	{
		if (static::$_table !== null) {
			return static::$_table;
		}

		static $table = null;
		if ($table !== null) {
			return $table;
		}

		$table = static::class;
		$pos = strrpos($table, '\\');
		if ($pos !== false) {
			$table = substr($table, $pos + 1);
		}
		return $table = self::snake_case($table);
	}

	public static function primaryKey(): string
	{
		if (static::$_primary !== null) {
			return static::$_primary;
		}
		return 'id';
	}

	public static function reference(): object
	{
		return new Reference(static::class);
	}

	protected static function snake_case(string $str): string
	{
		$out = lcfirst($str[0]);
		for($i=1; $i < strlen($str); $i++) {
			$chr = $str[$i];
			if ('A' <= $chr && $chr <= 'Z') {
				$out .= '_' . strtolower($chr);
			} else {
				$out .= $chr;
			}
		}
		return $out;
	}

	public function __set(string $prop, $value): void
	{
		$this->props[$prop] = $value;
		$this->dirty[] = $prop;
	}

	public function __get(string $prop)
	{
		return $this->props[$prop];
	}

	public function __isset(string $prop): boolean
	{
		return isset($this->props[$prop]);
	}

	public function data(): array
	{
		return $this->props;
	}

	protected function dirtyData(): array
	{
		$dirty = array_unique($this->dirty);
		$data = [];
		foreach ($dirty as $dirt) {
			$data[$dirt] = $this->$dirt;
		}
		return $data;
	}
}
