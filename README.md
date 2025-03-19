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

### 5. Homogonize GTINs for SQL storage
```php
 /**
  * Validate and convert the given GTIN to its proper format, leading 0's removed
  * (for easy SQL comparison between UPC-12 and EAN-13). Also translate ITF-14 bulk items
  * to EAN-13.
  *
  * @param string $originalGtin The original GTIN to validate and convert.
  * @return string|null Returns the validated and formatted GTIN, or null if invalid.
  */
 private function validateGtin(string $originalGtin)
 {
     if (!empty($originalGtin)) {
         // Identify and validate the GTIN
         $gtinInfo = GTIN::identifyBarcodeType($originalGtin);
         if (!$gtinInfo) return null;
         if ($gtinInfo['type'] === 'ITF-14') {
             $trimmedGtin = GTIN::itf14ToEan13($gtinInfo['full_form']);
         } else {
             $trimmedGtin = $gtinInfo['full_form'];
         }
         return ltrim($trimmedGtin, 0);
     }
     return null;
 }

```

---

### 6. Integrate with a Dataset (e.g., Laravel Model)
Use a custom method to process and fix database records (as shown in our `processRecord` example):
```php
use App\Models\Item;
use Sfinktah\String\GTIN;

 /**
  * Validate and save an EAN-13 barcode with leading 0's trimmmed, or
  * null if the GTIN is invalid
  *
  * @param Item $record
  */
 private function processRecord(Item $record)
 {
     $originalGtin = $record->gtin;

     if (!empty($originalGtin)) {
         // Identify and validate the GTIN
         $gtinInfo = GTIN::identifyBarcodeType(ltrim($originalGtin, 0));

         if ($gtinInfo) {
             if ($gtinInfo['type'] === 'ITF-14') {
                 $fixedGtin = GTIN::itf14ToEan13($gtinInfo['full_form']);
             } else if ($gtinInfo['type'] === 'UPC-12') {
                 // As we trim the result, this step doesn't really do anything useful,
                 // though if you want uniform EAN-13 then leave it in.
                 $fixedGtin = GTIN::upc12ToEan13($gtinInfo['full_form']);
             } else {
                 $fixedGtin = $gtinInfo['full_form'];
             }
             if (!GTIN::isValidEAN13($fixedGtin)) {
                 $this->info('Converted to invalid EAN13: ' . $fixedGtin);
             }
             
             // Trim leading 0s, this doesn't alter the validity of checksums.
             $trimmedGtin = ltrim($fixedGtin, 0);
             if ($trimmedGtin !== $originalGtin) {
                 // Update the GTIN if it was corrected
                 $record->gtin = $trimmedGtin;
                 $record->save();
                 $this->info(sprintf("Corrected %s GTIN for record ID %-7s: %-14s -> %-14s", $gtinInfo['type'], $record->id, $originalGtin, $trimmedGtin));
             }
         } else {
             $this->warn("Invalid GTIN for record ID {$record->id}: $originalGtin");
             $record->gtin = null;
             $record->save();
         }
     }
 }
```

---

## License

This package is licensed under the MIT license.