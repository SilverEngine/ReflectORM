<?php

namespace Silver\Database\Parts;

class Paren extends Part
{
	private $form;

	public function __construct(string $form)
	{
		$this->form = $form;
	}

	protected static function compile(object $q): array
	{
		return [ "({$q->form})" ];
	}
}
