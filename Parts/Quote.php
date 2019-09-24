<?php

namespace Silver\Database\Parts;

class Quote extends Part
{

	private $value;
	private $quote;

	public function __construct(string $value, string $quote_char)
	{
		$this->value = $value;
		$this->quote = $quote_char;
	}

	protected static function compile(object $q): array
	{
		$quote = $q->quote;
		$value = $q->value;

		switch($quote) {
		case '`':
		case '"':
		case "'":
			$value = str_replace($quote, $quote.$quote, $value);
			break;
		default:
			throw new \Exception("Unknown quote char: $quote");
		}

		return [ $quote . $value . $quote ];
	}
}
