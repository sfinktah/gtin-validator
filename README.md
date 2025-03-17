# GTIN Validator

A utility library for validating, calculating, and converting GTIN barcodes, including support for ITF-14, EAN-13, and UPC-12.

## How It Works
1. **Validation**:
   - Validates GTIN formats like ITF-14, EAN-13, and UPC-12 using standard check digit calculations and regular expressions.

2. **Conversion**:
   - Converts formats like ITF-14 to EAN-13 by dropping the packaging identifier and recalculating the checksum.
   - Converts UPC-12 into its respective GTIN-13 format by adding a leading zero.

3. **Fixing Invalid Barcodes**:
   - Identifies the type of a barcode (UPC-12, EAN-13, or ITF-14) and fixes it by padding or reformatting it based on its length and checksum.

4. **Duplicate Detection**:
   - Can be leveraged to detect duplicate barcodes in datasets by combining with custom logic.

## Installation

Install the package via Composer:

```bash
composer require sfinktah/gtin-validator
```

## Example Usage

Here are examples of how to use the `Sfinktah\String\GTIN` class in various scenarios.

---

### 1. Validate an ITF-14 Barcode
Use the `isValidITF14` method to verify if an ITF-14 barcode is valid:
```php
use Sfinktah\String\GTIN;

$itf14 = "10855100009555";

if (GTIN::isValidITF14($itf14)) {
    echo "The ITF-14 barcode is valid.\n";
} else {
    echo "The ITF-14 barcode is invalid.\n";
}
```

---

### 2. Convert ITF-14 to EAN-13
Remove the first digit of an ITF-14 barcode and calculate its new checksum:
```php
$itf14 = "10855100009555";
$ean13 = GTIN::itf14ToEan13($itf14);

if ($ean13) {
    echo "Converted EAN-13: $ean13\n";
} else {
    echo "Failed to convert ITF-14 to EAN-13.\n";
}
```

---

### 3. Validate an UPC-12 Barcode
Use the `isValidUPC12` method to check if a UPC-12 is valid:
```php
$upc12 = "012345678905";

if (GTIN::isValidUPC12($upc12)) {
    echo "The UPC-12 barcode is valid.\n";
} else {
    echo "The UPC-12 barcode is invalid.\n";
}
```

---

### 4. Identify Unknown Barcodes
Pass a barcode of unknown type for validation and formatting into its correct GTIN format:
```php
$unknownBarcode = "12345678905"; // Example: Too short for ITF-14
$gtinInfo = GTIN::identifyBarcodeType($unknownBarcode);

if ($gtinInfo) {
    echo "Barcode Type: {$gtinInfo['type']}\n";
    echo "Full Formatted Barcode: {$gtinInfo['full_form']}\n";
} else {
    echo "The barcode could not be validated as UPC-12, EAN-13, or ITF-14.\n";
}
```

---

### 5. Integrate with a Dataset (e.g., Laravel Model)
Use a custom method to process and fix database records (as shown in our `processRecord` example):
```php
use App\Models\TSummitCsv;
use Sfinktah\String\GTIN;

function processRecord(TSummitCsv $record)
{
    $originalGtin = $record->gtin;
    
    if (!empty($originalGtin)) {
        // Identify the GTIN type and validate
        $gtinInfo = GTIN::identifyBarcodeType($originalGtin);

        if ($gtinInfo) {
            $fixedGtin = $gtinInfo['full_form'];

            if ($fixedGtin !== $originalGtin) {
                // Update the GTIN if fixed
                $record->gtin = $fixedGtin;
                $record->save();
                echo "Fixed GTIN: $originalGtin -> $fixedGtin\n";
            }
        } else {
            echo "Invalid GTIN: $originalGtin\n";
        }
    }
}
```

---

## License

This package is licensed under the MIT license.