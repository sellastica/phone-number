<?php
namespace Sellastica\PhoneNumber;

class TyposReplacer
{
	/**
	 * @param string $number
	 * @return string
	 */
	public static function replace(string $number): string
	{
		return str_ireplace(['e', 'i', 'l', 'b', 'o'], ['3', '1', '1', '8', '0'], $number);
	}
}