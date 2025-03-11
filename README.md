# GTIN Validator

A utility library for validating, calculating, and converting GTIN-14 barcodes including ITF-14 into UPC-13.

### How It Works:
1. **Validation**:
    - The function ensures the input is a 14-digit string with a valid checksum.

2. **Drop Packaging Indicator**:
    - The first digit of the ITF-14 is dropped, leaving 13 potential digits.

3. **Check Digit Calculation**:
    - The checksum for UPC-13/EAN-13 is calculated using the formula:
        - Multiply odd-positioned digits by 1.
        - Multiply even-positioned digits by 3.
        - Sum both products.
        - Find the smallest number that, when added to the sum, results in a multiple of 10.

4. **Output**:
    - Concatenate the 12 initial digits with the recalculated checksum for the UPC-13.

### Example Input & Output
1. **Input**: `10012345678905` (ITF-14)
    - Drop the leading `1`: `0012345678905`
    - Recalculate checksum: `001234567890 + checksum`
    - **Output**: `0012345678907`

2. **Input**: `20098765432109` (ITF-14)
    - Drop the leading `2`: `0098765432109`
    - Recalculate checksum: `009876543210 + checksum`
    - **Output**: `0098765432107`


## Installation

Install the package via Composer:

```bash
composer require sfinktah/gtin-validator
```

## Usage

Here is an example of how to use the `Sfinktah\String\GTIN` class:

```php
use Sfinktah\String\GTIN;

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
```

## License

This package is licensed under the MIT license.