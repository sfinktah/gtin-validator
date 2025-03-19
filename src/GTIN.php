<?php

namespace Sfinktah\String;

class GTIN
{
    // Constants for validating barcode lengths
    public const UPC12_REGEX = '/^\d{12}$/'; // NEW: Regex for UPC-12 validation
    public const EAN13_REGEX = '/^\d{13}$/';
    public const ITF14_REGEX = '/^\d{14}$/';
    public const UPC12_PREFIX_REGEX = '/^\d{11}$/';
    public const EAN13_PREFIX_REGEX = '/^\d{12}$/';
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
     * Validates a UPC-12 barcode by verifying its length and checksum.
     *
     * @param string $upc12 The 12-digit UPC-12 barcode.
     * @return bool True if valid, false otherwise.
     */
    public static function isValidUPC12(string $upc12): bool
    {
        // Validate format and length
        if (!self::validateBarcode($upc12, self::UPC12_REGEX)) {
            return false;
        }

        // Extract data and calculate checksum
        $data = substr($upc12, 0, 11);
        $providedCheckDigit = (int) $upc12[11];
        $calculatedCheckDigit = self::calculateUPC12CheckDigit($data);

        return $providedCheckDigit === $calculatedCheckDigit;
    }

    /**
     * Validates an EAN-13 barcode by length, format, and check digit.
     *
     * @param string $ean13 The 13-digit EAN-13 barcode.
     * @return bool True if valid, false otherwise.
     */
    public static function isValidEAN13(string $ean13): bool
    {
        // Ensure the barcode has exactly 13 digits using regex
        if (!self::validateBarcode($ean13, self::EAN13_REGEX)) {
            return false;
        }

        // Extract the first 12 digits (data) and provided check digit
        $data = substr($ean13, 0, 12);
        $providedCheckDigit = (int) $ean13[12];

        // Calculate the check digit
        $calculatedCheckDigit = self::calculateEAN13CheckDigit($data);

        // Compare calculated and provided check digits
        return $providedCheckDigit === $calculatedCheckDigit;
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
     * @param string $data The prefix (e.g., first 13 digits of ITF-14 or 12 digits of EAN-13).
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
     * Calculates the check digit for a UPC-12 barcode prefix (first 11 digits).
     *
     * @param string $data The first 11 digits of the UPC-12 barcode.
     * @return int The check digit (0-9).
     */
    public static function calculateUPC12CheckDigit(string $data): int
    {
        if (!self::validateBarcode($data, self::UPC12_PREFIX_REGEX)) { // Ensure 11 digits
            throw new \InvalidArgumentException('Input must be exactly 11 digits.');
        }
        return self::calculateCheckDigit($data, 3, 1); // UPC uses the same weights as ITF and EAN
    }

    /**
     * Converts a UPC-12 barcode to a EAN-13 barcode by prefixing it with a leading zero.
     *
     * @param string $upc12 The 12-digit UPC-12 barcode.
     * @return string|null The resulting 13-digit EAN-13 barcode or null if invalid.
     */
    public static function upc12ToEan13(string $upc12): ?string
    {
        if (!self::isValidUPC12($upc12)) {
            return null;
        }

        // Add leading zero and calculate checksum
        $gtin13Prefix = '0' . substr($upc12, 0, 11);
        $checkDigit = self::calculateEAN13CheckDigit($gtin13Prefix);

        return substr($gtin13Prefix, 0, 12) . $checkDigit;
    }

    /**
     * Converts an ITF-14 barcode to an EAN-13 barcode by dropping the leading digit.
     *
     * @param string $itf14 The 14-digit ITF-14 barcode.
     * @return string|null The resulting 13-digit EAN-13 barcode or null if invalid.
     */
    public static function itf14ToEan13(string $itf14): ?string
    {
        if (!self::isValidITF14($itf14)) {
            return null;
        }

        // Drop the first digit and calculate checksum
        $ean13Prefix = substr($itf14, 1, 12);
        $checkDigit = self::calculateEAN13CheckDigit($ean13Prefix);

        return $ean13Prefix . $checkDigit;
    }

    /**
     * Calculates the check digit for a 12-digit EAN-13 (EAN-13) barcode prefix.
     *
     * @param string $prefix The 12-digit string of numbers.
     * @return int The check digit (0-9).
     */
    public static function calculateEAN13CheckDigit(string $prefix): int
    {
        if (!self::validateBarcode($prefix, self::EAN13_PREFIX_REGEX)) {
            throw new \InvalidArgumentException('Input must be exactly 12 digits.');
        }
        return self::calculateCheckDigit($prefix, 1, 3);
    }


    /**
     * Identifies the type of barcode by padding and validating it.
     *
     * @param string $barcode The input barcode (of unknown length and type).
     * @return array|null An array with 'type' (UPC-12, EAN-13, ITF-14) and 'full_form' or null if invalid.
     */
    public static function identifyBarcodeType(string $barcode, bool $noUpc12 = false, bool $noEan13 = false, bool $noItf14 = false): ?array
    {
        // Normalize barcode (remove unnecessary spaces, etc.)
        $barcode = trim($barcode);

        // Prepare padded forms
        $paddedTo12 = str_pad($barcode, 12, '0', STR_PAD_LEFT); // Pad to 12 digits
        $paddedTo13 = str_pad($barcode, 13, '0', STR_PAD_LEFT); // Pad to 13 digits
        $paddedTo14 = str_pad($barcode, 14, '0', STR_PAD_LEFT); // Pad to 14 digits

        // Try validating UPC-12
        if (!$noUpc12 && self::isValidUPC12($paddedTo12)) {
            return [
                'type' => 'UPC-12',
                'full_form' => $paddedTo12,
            ];
        }

        // Try validating EAN-13
        if (!$noEan13 && self::isValidEAN13($paddedTo13)) {
            return [
                'type' => 'EAN-13',
                'full_form' => $paddedTo13,
            ];
        }

        // Try validating ITF-14
        if (!$noItf14 && self::isValidITF14($paddedTo14)) {
            return [
                'type' => 'ITF-14',
                'full_form' => $paddedTo14,
            ];
        }

        // If none match, return null
        return null;
    }

    /**
     * Validate and convert the given GTIN to EAN-13. Translate ITF-14 bulk items
     * to EAN-13 singular item codes.
     *
     * @param string|null $originalGtin The original GTIN to validate and convert.
     * @param bool $stripLeadingZeroes Trim leading 0's from result.
     * @param \Sfinktah\String\GTIN|null $gtinInfo Optionally return validation information from $originalGtin
     * @return string|null Returns the validated and formatted EAN-13, or null if invalid.
     */
    public static function normalizeAsEan13(?string $originalGtin, bool $stripLeadingZeroes = false, ?GTIN &$gtinInfo = null) : ?string
    {
        if (!empty($originalGtin)) {
            // Identify and validate the GTIN
            $gtinInfo = self::identifyBarcodeType(ltrim($originalGtin, 0));
            if (!$gtinInfo) return null;
            if ($gtinInfo['type'] === 'ITF-14') {
                $fixedGtin = self::itf14ToEan13($gtinInfo['full_form']);
            } else if ($gtinInfo['type'] === 'UPC-12') {
                // As we trim the result, this step doesn't really do anything useful,
                // though if you want uniform EAN-13 then leave it in.
                $fixedGtin = self::upc12ToEan13($gtinInfo['full_form']);
            } else {
                $fixedGtin = $gtinInfo['full_form'];
            }
            if (!self::isValidEAN13($fixedGtin)) {
                throw new \RuntimeException('Converted to invalid EAN13: ' . $fixedGtin);
            }
            return $stripLeadingZeroes ? ltrim($fixedGtin, 0) : $fixedGtin;
        }
        return null;
    }

    public static function test() {
        // use Sfinktah\String\GTIN;

        // Example ITF-14 barcode
        $itf14 = "10855100009555";

        if (GTIN::isValidITF14($itf14)) {
            echo "The ITF-14 barcode is valid.\n";

            $ean13 = GTIN::itf14ToEan13($itf14);
            if ($ean13) {
                echo "Converted EAN-13: $ean13\n";
            } else {
                echo "Failed to convert to EAN-13.\n";
            }
        } else {
            echo "The ITF-14 barcode is invalid.\n";
        }



        $ean13 = "4006381333931"; // Example EAN-13 barcode

        // Validate EAN-13
        if (GTIN::isValidEAN13($ean13)) {
            echo "The EAN-13 barcode is valid.\n";
        } else {
            echo "The EAN-13 barcode is invalid.\n";
        }

        $upc12 = "012345678905"; // Example UPC-12 barcode

        // Validate UPC-12
        if (GTIN::isValidUPC12($upc12)) {
            echo "The UPC-12 barcode is valid.\n";

            // Convert to EAN-13
            $gtin13 = GTIN::upc12ToEan13($upc12);
            if ($gtin13) {
                echo "The corresponding EAN-13 is: $gtin13\n";
            }
        } else {
            echo "The UPC-12 barcode is invalid.\n";
        }


        $unknownBarcode = "10315605012"; // Example input: 11 digits

        $result = GTIN::identifyBarcodeType($unknownBarcode);
        if ($result) {
            echo "The barcode is identified as {$result['type']}.\n";
            echo "Full form: {$result['full_form']}\n";
        } else {
            echo "The barcode is invalid for UPC-12, EAN-13, and ITF-14.\n";
        }

    }
}

