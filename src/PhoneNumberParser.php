<?php
namespace Sellastica\PhoneNumber;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\ValidationResult;
use Sellastica\PhoneNumber\Exception\PhoneNumberParserException;

class PhoneNumberParser
{
	/** @var string */
	private $number;
	/** @var null|string */
	private $countryCode;
	/** @var PhoneNumberUtil */
	private $lib;


	/**
	 * @param string $number
	 * @param string|null $countryCode
	 * @param bool $replaceTypos
	 */
	public function __construct(string $number, string $countryCode = null, bool $replaceTypos = true)
	{
		//altough PhoneNumberUtil can convert string-like numbers to digits, e.g. 1-800-FLOWERS to 1-800-356-9377,
		//we suggest to not allow these numbers and replace possible typos instead (e.g. O vs. 0 etc.)
		$this->number = $replaceTypos ? TyposReplacer::replace($number) : $number;
		$this->countryCode = $countryCode;
		$this->lib = PhoneNumberUtil::getInstance();
	}

	/**
	 * @return PhoneNumber
	 * @throws PhoneNumberParserException
	 */
	public function parse(): PhoneNumber
	{
		try {
			$numberObject = $this->lib->parse($this->number, $this->countryCode);
			//isPossibleNumber method is less strict than isValidNumber method, but returns a reason
			$reason = $this->lib->isPossibleNumberWithReason($numberObject);
			if ($reason !== ValidationResult::IS_POSSIBLE) {
				$this->throwParserError($reason);
			}

			//isValidNumber method returns bool only, but is more strict
			if (!$this->lib->isValidNumberForRegion($numberObject, $this->countryCode)) {
				$this->throwParserError();
			}

			return $numberObject;
		} catch (NumberParseException $e) {
			$this->throwParserError();
		}
	}

	/**
	 * @param int|null $reason
	 * @throws PhoneNumberParserException
	 */
	private function throwParserError(int $reason = null): void
	{
		switch ($reason) {
			case ValidationResult::INVALID_COUNTRY_CODE:
				throw new Exception\PhoneNumberParserException('system.notices.phone_number_has_invalid_country_code');
				break;
			case ValidationResult::TOO_SHORT:
				throw new Exception\PhoneNumberParserException('system.notices.phone_number_is_too_short');
				break;
			case ValidationResult::TOO_LONG:
				throw new Exception\PhoneNumberParserException('system.notices.phone_number_is_too_long');
				break;
			case ValidationResult::INVALID_LENGTH:
				throw new Exception\PhoneNumberParserException('system.notices.phone_number_has_invalid_length');
				break;
			default:
				throw new Exception\PhoneNumberParserException('system.notices.invalid_phone_number_format');
				break;
		}
	}

	/**
	 * @param string $number
	 * @param string $countryCode
	 * @param int $format
	 * @return string
	 */
	public static function format(
		string $number,
		string $countryCode,
		int $format = PhoneNumberFormat::E164
	): string
	{
		$phoneNumberParser = new PhoneNumberParser($number, $countryCode);
		$phoneNumber = $phoneNumberParser->parse();
		return PhoneNumberUtil::getInstance()->format(
			$phoneNumber, $format
		);
	}
}