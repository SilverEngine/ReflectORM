<?php

namespace Silver\Database\Parts;

class SingleQuote extends Quote
{
	public function __construct(string $value)
	{
		parent::__construct($value, "'");
	}
}
