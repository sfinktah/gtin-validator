<?php

namespace Sfinktah\String;

class GTIN
{
    // Constants for validating barcode lengths
    public const ITF14_REGEX = '/^\d{14}$/';
    public const UPC13_PREFIX_REGEX = '/^\d{12}$/';
    public const ITF14_PREFIX_REGEX = '/^\d{13}$/';

    /**
     * Validates if the provided barcode matches the specified regex pattern.
     *
     * @param string $barcode The barcode to validate.
     * @param string $pattern The regex pattern to use for validation.
     * @return bool True if valid, false otherwise.
     */
    public static function validateBarcode(string $barcode, string $pattern): bool
    {
        return preg_match($pattern, $barcode) === 1;
    }

    /**
     * Validates an ITF-14 barcode by checking the length and verifying its checksum.
     *
     * @param string $itf14 The 14-digit ITF-14 barcode.
     * @return bool True if valid, false otherwise.
     */
    public static function isValidITF14(string $itf14): bool
    {
        // Validate length and format
        if (!self::validateBarcode($itf14, self::ITF14_REGEX)) {
            return false;
        }

        // Extract data and calculate checksum
        $data = substr($itf14, 0, 13);
        $providedCheckDigit = (int) $itf14[13];
        $calculatedCheckDigit = self::calculateITF14CheckDigit($data);

        return $providedCheckDigit === $calculatedCheckDigit;
    }

    /**
     * Calculates the check digit for an ITF-14 or similar barcode prefix.
     *
     * @param string $data The prefix (e.g., first 13 digits of ITF-14 or 12 digits of UPC-13).
     * @param int $weightOdd Multiplier for odd-position digits.
     * @param int $weightEven Multiplier for even-position digits.
     * @return int The check digit (0-9).
     */
    public static function calculateCheckDigit(string $data, int $weightOdd, int $weightEven): int
    {
        $sum = 0;

        for ($i = 0, $length = strlen($data); $i < $length; $i++) {
            $digit = (int) $data[$i];
            $sum += ($i % 2 === 0) ? $digit * $weightOdd : $digit * $weightEven;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Calculates the check digit for a 13-digit ITF-14 barcode prefix.
     *
     * @param string $data The first 13 digits of the ITF-14 barcode.
     * @return int The check digit (0-9).
     */
    public static function calculateITF14CheckDigit(string $data): int
    {
        if (!self::validateBarcode($data, self::ITF14_PREFIX_REGEX)) {
            throw new \InvalidArgumentException('Input must be exactly 13 digits.');
        }
        return self::calculateCheckDigit($data, 3, 1);
    }

    /**
     * Converts an ITF-14 barcode to a UPC-13 barcode by dropping the leading digit.
     *
     * @param string $itf14 The 14-digit ITF-14 barcode.
     * @return string|null The resulting 13-digit UPC-13 barcode or null if invalid.
     */
    public static function itf14ToUpc13(string $itf14): ?string
    {
        if (!self::isValidITF14($itf14)) {
            return null;
        }

        // Drop the first digit and calculate checksum
        $upc13Prefix = substr($itf14, 1, 12);
        $checkDigit = self::calculateUPC13CheckDigit($upc13Prefix);

        return $upc13Prefix . $checkDigit;
    }

    /**
     * Calculates the check digit for a 12-digit UPC-13 (EAN-13) barcode prefix.
     *
     * @param string $prefix The 12-digit string of numbers.
     * @return int The check digit (0-9).
     */
    public static function calculateUPC13CheckDigit(string $prefix): int
    {
        if (!self::validateBarcode($prefix, self::UPC13_PREFIX_REGEX)) {
            throw new \InvalidArgumentException('Input must be exactly 12 digits.');
        }
        return self::calculateCheckDigit($prefix, 1, 3);
    }
}

// use Sfinktah\String\GTIN;

// Example ITF-14 barcode
$itf14 = "10855100009555";

if (GTIN::isValidITF14($itf14)) {
    echo "The ITF-14 barcode is valid.\n";

    $upc13 = GTIN::itf14ToUpc13($itf14);
    if ($upc13) {
        echo "Converted UPC-13: $upc13\n";
    } else {
        echo "Failed to convert to UPC-13.\n";
    }
} else {
    echo "The ITF-14 barcode is invalid.\n";
}
