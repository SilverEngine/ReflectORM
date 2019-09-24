<?php

namespace Silver\Database\Parts;

class Raw extends Part
{

	private $value;

	public function __construct(string $value)
	{
		$this->value = $value;
	}

	protected static function compile(object$q): array
	{
		return [ $q->value ];
	}
}
