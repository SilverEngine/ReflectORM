<?php

namespace Silver\Database;

use \PDO;

abstract class Db
{
	private static $dbs = [];
	private static $default = null;
	private static $global_debug = false;
	private $debug = null;
	private $query = null;
	private static $tx_counter = [];

	private $fetch_style = PDO::FETCH_OBJ;

	/**
	 * @return mixed
	 */
	abstract public function toSql();

	// Optional virtual methods, used by ->first()
	public function getLimit(): int
	{
		throw new \Exception('Unable to get limit on ' . static::class);
	}
	public function limit(int $count): object
	{
		throw new \Exception('Unable to set limit for ' . static::class);
	}

	/**
	 * @param	   $name
	 * @param	   $dsn
	 * @param null $username
	 * @param null $password
	 */
	public static function connect(string $name, string $dsn, string $username = null, string $password = null): void
	{
		self::$dbs[$name] = function () use ($name, $dsn, $username, $password) {
			return new PDO(
				$dsn, $username, $password, [
				PDO::ATTR_ERRMODE			 => PDO::ERRMODE_EXCEPTION,
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
				]
			);
		};
	}

	/**
	 * @param bool $enabled
	 */
	public static function debugMode($enabled = true): void
	{
		self::$global_debug = $enabled;
	}

	/**
	 * @param bool $enabled
	 * @return $this
	 */
	public function debug(bool $enabled = true): object
	{
		$this->debug = $enabled;

		return $this;
	}

	/**
	 * @return bool|null
	 */
	public function isDebug(): bool
	{
		if (isset($this) && $this instanceof Db && $this->debug !== null) {
			return $this->debug;
		}

		return self::$global_debug;
	}

	/**
	 * @param $name
	 * @throws \Exception
	 */
	public static function setConnection(string $name): void
	{
		if (!isset(self::$dbs[ $name ])) {
			throw new \Exception("Connection '$name' not found.");
		}
		self::$default = $name;
	}

	/**
	 * @param $name
	 * @param $cb
	 */
	public static function withConnection(string $name, $cb): void
	{
		$prev = self::$default;
		self::setConnection($name);
		try {
			$cb();
		} finally {
			self::$default = $prev;
		}
	}

	/**
	 * @return array
	 */
	public static function connections(): array
	{
		return array_keys(self::$dbs);
	}

	/**
	 * @param null $name
	 * @return mixed
	 * @throws \Exception
	 */
	public static function connection(string $name = null)
	{
		if ($name === null) {
			$name = self::$default;
		}

		//		  dd($name);

		if ($name === null) {
			throw new \Exception("Not default connection found.");
		}

		$db = self::$dbs[$name];

		// Lazy loading
		if ($db and is_callable($db)) {
			$db = self::$dbs[ $name ] = $db();
		}

		if (!$db) {
			throw new \Exception("Connection '$name' not found.");
		}

		return $db;
	}

	/**
	 * @param $value
	 * @return mixed
	 * @throws \Exception
	 */
	public static function quote(string $value): string
	{
		switch ($type = gettype($value)) {
		case 'string':
			return self::connection()->quote($value);
		case 'integer':
		case 'double':
			return $value;
		default:
			throw new \Exception("Unable to quote value with type: $type");
		}
	}

	/**
	 * @param		$sql
	 * @param array $bindings
	 * @return mixed
	 */
	private static function raw(string $sql, array $bindings = []): object
	{
		$db = self::connection();
		$stmt = $db->prepare($sql);
		$stmt->execute($bindings);

		return $stmt;
	}

	/**
	 * @param $sql
	 * @return mixed
	 */
	public static function exec(string $sql): int
	{
		return self::connection()->exec($sql);
	}

	/**
	 * @param		$sql
	 * @param array $bindings
	 * @return static
	 */
	public static function query(string $sql, array $bindings = []): object
	{
		$q = new static;
		$q->query = self::raw($sql, $bindings);
		return $q;
	}


	/**
	 * @param bool $silent
	 * @return $this
	 */
	public function execute(): object
	{
		$sql = '' . $this;
		$bindings = $this->getBindings();

		if ($this->isDebug()) {
			echo "SQL: $sql\n";
			if ($bindings) {
				echo "BND: " . print_r($bindings, true);
			}
		}

		$this->query = self::raw($sql, $bindings);
		return $this;
	}

	public static function lastInsertId(): int
	{
		return self::connection()->lastInsertId();
	}

	// What should we do?
	/**
	 * @return mixed
	 */
	public function affected(): int
	{
		return $this->query->rowCount();
	}

	public function setFetchStyle(int $style): object
	{
		$this->fetch_style = $style;
		return $this;
	}

	// Fetching
	public function get(int $style = null): object
	{
		if ($style == null) {
			$style = $this->fetch_style;
		}

		if ($this->query === null) {
			$this->prepareSelect($style);
			$this->execute();
		}

		$this->setQueryMode($style);
		$result = $this->query->fetch();
		return $this->transformResult($result, $style);
	}

	public function single(): object
	{
		//TODO: ResultNotFoundException
		$res = $this->get(PDO::FETCH_NUM);
		return $res[0];
	}

	public function all(int $style = null, $callback = null): array
	{
		if ($style == null) {
			$style = $this->fetch_style;
		}

		$this->prepareSelect($style);
		$this->execute();
		$this->setQueryMode($style);
		$data = $this->query->fetchAll();
		$newdata = [];
		foreach($data as &$row) {
			$row = $this->transformResult($row, $style);
			if ($callback) {
				$row = $callback($row);
			}
			$newdata[] = $row;
		}
		return $newdata;
	}

	public function singleAll(): array
	{
		return $this->all(
			PDO::FETCH_NUM, function ($row) {
				return $row[0];
			}
		);
	}

	public function first(int $style = null): object
	{
		$old_limit = $this->getLimit();
		$this->limit(1);
		$result = $this->get($style);
		$this->limit($old_limit);

		$this->query->closeCursor();
		$this->query = null;

		return $result;
	}

	private function prepareSelect(int $style): void
	{
		if (is_object($style)) {
			$this->selectForModel(get_class($style));
		} else if (is_string($style) && class_exists($style)) {
			$this->selectForModel($style);
		}
	}

	private function setQueryMode(int $style): void
	{
		if (is_array($style)) {
			$this->query->setFetchMode(PDO::FETCH_ASSOC);
		} else if (is_object($style)) {
			$this->query->setFetchMode(PDO::FETCH_INTO, $style);
		} else if (class_exists($style)) {
			$this->query->setFetchMode(PDO::FETCH_CLASS, $style);
		} else if (is_string($style)) {
			$this->query->setFetchMode(PDO::FETCH_ASSOC);
		} else {
			$this->query->setFetchMode($style);
		}
	}

	private function transformResult(array $result, int $style): array
	{
		if ($result === null) {
			return null;
		}

		if (is_array($style)) {
			$r = [];
			foreach ($style as $key) {
				$r[$key] = $result[$key];
			}
			return $r;
		} else if (is_object($style)) {
			return $style;
		} else if (class_exists($style)) {
			return $result;
		} else if (is_string($style)) {
			return $result[$style];
		} else {
			return $result;
		}
	}

	// Fetch next?
	/**
	 * @return mixed
	 */
	// @Deprecated
	public function fetch(int $pdo_fetch_style = PDO::FETCH_OBJ): object
	{
		if ($this->query === null) {
			$this->execute(true);
		}

		if (class_exists($pdo_fetch_style)) {
			$this->query->setFetchMode(PDO::FETCH_CLASS, $pdo_fetch_style);
		} else {
			$this->query->setFetchMode($pdo_fetch_style);
		}

		return $this->query->fetch();
	}

	/**
	 * @return mixed
	 */
	// @Deprecated
	public function fetchAll(int $pdo_fetch_style = PDO::FETCH_OBJ): array
	{
		$this->execute(true);

		if (class_exists($pdo_fetch_style)) {
			$this->query->setFetchMode(PDO::FETCH_CLASS, $pdo_fetch_style);
		} else {
			$this->query->setFetchMode($pdo_fetch_style);
		}
		return $this->query->fetchAll();
	}

	/**
	 * NOTE, XXX: This is public, becouse mysql need to check if
	 * connection is within transaction.
	 * Maybe we should make an alias function transactionLevel()
	 *
	 * @return mixed
	 */
	public static function getTxCounter(): int
	{
		$db = self::$default;

		if (!isset(self::$tx_counter[ $db ])) {
			self::$tx_counter[ $db ] = 0;
		}

		return self::$tx_counter[ $db ];
	}

	/**
	 * @param $num
	 * @return mixed
	 */
	private static function setTxCounter(int $num): int
	{
		$db = self::$default;

		return self::$tx_counter[ $db ] = $num;
	}

	/**
	 * @param int $delta
	 * @return mixed
	 */
	private static function incTxCounter(int $delta = 1): int
	{
		$num = self::getTxCounter();
		self::setTxCounter($num + $delta);

		return $num + $delta;
	}

	// Transactions
	/**
	 *
	 */
	public static function beginTransaction(): void
	{
		$conn = self::connection();
		$level = self::incTxCounter();

		if ($level == 1) {
			$conn->beginTransaction();
		} else {
			self::exec('SAVEPOINT LEVEL' . $level);
		}
	}

	/**
	 * @throws \Exception
	 */
	public static function commit(): void
	{
		$conn = self::connection();
		$level = self::incTxCounter(-1) + 1;

		if ($level < 1) {
			throw new \Exception("There is no active transaction.");
		} elseif ($level == 1) {
			$conn->commit();
		} else {
			self::exec('RELEASE SAVEPOINT LEVEL' . $level);
		}
	}

	/**
	 * @throws \Exception
	 */
	public static function rollBack(): void
	{
		$conn = self::connection();
		$level = self::incTxCounter(-1) + 1;

		if ($level < 1) {
			throw new \Exception("There is no active transaction.");
		} elseif ($level == 1) {
			$conn->rollBack();
		} else {
			self::exec('ROLLBACK TO SAVEPOINT LEVEL' . $level);
		}
	}

	/**
	 * @param	   $cb
	 * @param bool $suppress
	 * @return bool|void
	 * @throws \Exception
	 */
	public static function transaction($cb, bool $suppress = false): bool
	{
		try {
			self::beginTransaction();
			$cb();

			self::commit();
			return true;
		} catch (\Exception $e) {
			self::rollBack();
			if ($suppress) {
				return false;
			} else {
				throw $e;
			}
		}
	}

	/**
	 * @return mixed
	 */
	public static function driverName(): string
	{
		$conn = self::connection();

		return $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
	}
}
