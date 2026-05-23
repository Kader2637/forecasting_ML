<?php

namespace App\Services;

use App\Models\MasterItem;
use App\Models\MasterItemStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use stdClass;

/**
 * Service untuk update Buffer Stock dengan ROP dari CSV
 * Implementasi native PHP dari Python script update_rop_to_buffer_stock.py
 */
class ROPBufferStockUpdaterService
{
    private $csvPath;
    private $mappingFile;
    private $productMapping = [];
    private $dbItems = [];

    public function __construct(
        $csvPath = 'buffer_stock_per_produk.csv',
        $mappingFile = 'product_mapping.json'
    ) {
        $this->csvPath = $this->getFilePath($csvPath);
        $this->mappingFile = $this->getFilePath($mappingFile);
        $this->loadProductMapping();
    }

    /**
     * Get absolute file path
     */
    private function getFilePath($filename): string
    {
        // Try multiple locations
        $paths = [
            base_path($filename),
            base_path('python/' . $filename),
            public_path($filename),
            storage_path($filename)
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return base_path($filename);
    }

    /**
     * Load product mapping dari JSON
     */
    private function loadProductMapping(): void
    {
        if (!file_exists($this->mappingFile)) {
            Log::warning("⚠ Mapping file tidak ditemukan: " . basename($this->mappingFile));
            return;
        }

        try {
            $json = file_get_contents($this->mappingFile);
            if ($json === false) {
                Log::warning("⚠ Tidak bisa membaca mapping file");
                return;
            }

            $rawMapping = json_decode($json, true);
            if ($rawMapping === null) {
                Log::warning("⚠ Mapping file JSON tidak valid");
                return;
            }

            if (isset($rawMapping['products']) && is_array($rawMapping['products'])) {
                // Format: {"products": {"SKU": {"mapped_to": "DB Name"}}}
                foreach ($rawMapping['products'] as $sku => $data) {
                    if (is_array($data) && isset($data['mapped_to'])) {
                        $this->productMapping[$sku] = $data['mapped_to'];
                    }
                }
            } else if (is_array($rawMapping)) {
                // Format flat: {"SKU": "DB Name"}
                $this->productMapping = $rawMapping;
            }

            Log::info("✓ Loaded " . count($this->productMapping) . " product mappings");
        } catch (\Exception $e) {
            Log::error("✗ Error loading mapping file: " . $e->getMessage());
            $this->productMapping = [];
        }
    }

    /**
     * Get database items mapping
     */
    private function getDatabaseItems(): array
    {
        if (!empty($this->dbItems)) {
            return $this->dbItems;
        }

        try {
            $items = MasterItem::where('status_item', 'active')
                ->select(['item_id', 'name_item', 'code_item'])
                ->get();

            if ($items && count($items) > 0) {
                foreach ($items as $item) {
                    if (!empty($item->name_item)) {
                        $this->dbItems[strtolower($item->name_item)] = $item->item_id;
                    }
                    if (!empty($item->code_item)) {
                        $this->dbItems[strtolower($item->code_item)] = $item->item_id;
                    }
                }
                Log::info("✓ Loaded " . count($items) . " items dari database");
            }
        } catch (\Exception $e) {
            Log::error("✗ Error getting database items: " . $e->getMessage());
        }

        return $this->dbItems;
    }

    /**
     * Normalize nama produk untuk matching
     */
    private function normalizeProductName($name): string
    {
        if (!is_string($name)) {
            $name = (string)$name;
        }

        // Convert to lowercase
        $normalized = strtolower(trim($name));
        // Remove special characters except numbers and letters
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);
        // Remove extra spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }

    /**
     * Find item_id dari product name
     */
    private function findItemId($productName): ?int
    {
        if (!is_string($productName)) {
            $productName = (string)$productName;
        }

        $productName = trim($productName);
        if (empty($productName)) {
            return null;
        }

        // 1. Try exact match dengan mapping
        if (isset($this->productMapping[$productName])) {
            $mappedName = strtolower($this->productMapping[$productName]);
            if (isset($this->dbItems[$mappedName])) {
                return (int)$this->dbItems[$mappedName];
            }
        }

        // 2. Try direct match di database items
        $normalized = $this->normalizeProductName($productName);
        foreach ($this->dbItems as $dbName => $itemId) {
            $dbNormalized = $this->normalizeProductName($dbName);
            if (!empty($dbNormalized) && !empty($normalized)) {
                if (strpos($normalized, $dbNormalized) !== false || 
                    strpos($dbNormalized, $normalized) !== false) {
                    return (int)$itemId;
                }
            }
        }

        return null;
    }

    /**
     * Read ROP values dari CSV
     */
    private function readRopFromCsv(): ?array
    {
        $csvPath = $this->csvPath;
        if (!is_string($csvPath)) {
            Log::error("✗ CSV path tidak valid");
            return null;
        }

        if (!file_exists($csvPath)) {
            Log::error("✗ CSV file tidak ditemukan: " . basename($csvPath));
            return null;
        }

        try {
            $rows = [];
            if (($handle = fopen($csvPath, 'r')) !== false) {
                $header = fgetcsv($handle);

                // Validate required columns
                if ($header === false || !is_array($header)) {
                    Log::error("✗ CSV file kosong atau tidak valid");
                    fclose($handle);
                    return null;
                }

                $productCol = null;
                $ropCol = null;

                foreach ($header as $index => $col) {
                    if (!is_string($col)) {
                        continue;
                    }
                    
                    $colLower = strtolower(trim($col));
                    if (in_array($colLower, ['produk', 'product', 'name'])) {
                        $productCol = $index;
                    }
                    if (in_array($colLower, ['rop_unit', 'rop', 'buffer_stock', 'buffer_stock_unit'])) {
                        $ropCol = $index;
                    }
                }

                if ($productCol === null || $ropCol === null) {
                    Log::error("✗ CSV missing required columns (Produk, ROP_Unit atau Buffer_Stock_Unit)");
                    fclose($handle);
                    return null;
                }

                while (($data = fgetcsv($handle)) !== false) {
                    if (!is_array($data) || count($data) <= max($productCol, $ropCol)) {
                        continue;
                    }

                    $productName = isset($data[$productCol]) ? trim((string)$data[$productCol]) : null;
                    $ropValue = isset($data[$ropCol]) ? trim((string)$data[$ropCol]) : null;

                    if (!empty($productName) && !empty($ropValue) && is_numeric($ropValue)) {
                        $rows[] = [
                            'Produk' => $productName,
                            'ROP_Unit' => (float)$ropValue
                        ];
                    }
                }

                fclose($handle);
            } else {
                Log::error("✗ Tidak bisa membuka file CSV");
                return null;
            }

            Log::info("✓ Loaded CSV dengan " . count($rows) . " produk");
            return count($rows) > 0 ? $rows : null;
        } catch (\Exception $e) {
            Log::error("✗ Error reading CSV: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update buffer_stock di master_items_stock dengan ROP values
     */
    public function updateBufferStock(int $inventoryId = 1): array
    {
        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $updatedItems = [];
        $skippedItems = [];
        $errorItems = [];

        try {
            // Read CSV
            $csvData = $this->readRopFromCsv();
            if ($csvData === null || !is_array($csvData) || count($csvData) === 0) {
                $errorResult = [
                    'success' => false,
                    'message' => 'Gagal membaca file CSV atau file kosong',
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => 0
                ];
                return $this->sanitizeResult($errorResult);
            }

            // Get database items
            $this->getDatabaseItems();
            if (empty($this->dbItems)) {
                $errorResult = [
                    'success' => false,
                    'message' => 'Tidak ada item di database',
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => 0
                ];
                return $this->sanitizeResult($errorResult);
            }

            Log::info("UPDATING BUFFER STOCK DENGAN ROP VALUES");
            Log::info("Source: CSV file, Target: master_items_stock (inventory_id=$inventoryId)");

            // Update each product
            DB::beginTransaction();

            foreach ($csvData as $row) {
                if (!is_array($row)) {
                    $errorCount++;
                    continue;
                }

                $productName = isset($row['Produk']) ? (string)$row['Produk'] : null;
                $ropValue = isset($row['ROP_Unit']) ? $row['ROP_Unit'] : null;

                if (empty($productName) || $ropValue === null) {
                    $errorCount++;
                    continue;
                }

                try {
                    // Validate ROP value
                    if (!is_numeric($ropValue)) {
                        Log::warning("⊘ SKIP: Produk '$productName' - ROP value tidak valid (bukan angka)");
                        $skippedCount++;
                        $skippedItems[] = $productName;
                        continue;
                    }

                    // Find item_id
                    $itemId = $this->findItemId($productName);

                    if ($itemId === null) {
                        Log::warning("⊘ SKIP: Produk '$productName' tidak ditemukan di database");
                        $skippedCount++;
                        $skippedItems[] = $productName;
                        continue;
                    }

                    // Round ROP to integer
                    $ropInt = (int) round((float) $ropValue, 0);

                    // Validate ROP value
                    if ($ropInt < 0) {
                        Log::warning("⊘ SKIP: Produk '$productName' - ROP value negatif ($ropInt)");
                        $skippedCount++;
                        $skippedItems[] = $productName;
                        continue;
                    }

                    // Check if record exists
                    $itemStock = MasterItemStock::where('item_id', $itemId)
                        ->where('inventory_id', $inventoryId)
                        ->first();

                    if ($itemStock) {
                        // Update existing record
                        $itemStock->update(['buffer_stock' => $ropInt]);
                    } else {
                        // Insert new record
                        MasterItemStock::create([
                            'item_id' => $itemId,
                            'inventory_id' => $inventoryId,
                            'stock' => 0,
                            'buffer_stock' => $ropInt
                        ]);
                    }

                    Log::info("✓ $productName: buffer_stock = $ropInt (ROP)");
                    $updatedCount++;
                    $updatedItems[] = $productName;

                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    Log::error("✗ ERROR updating '$productName': $errorMsg");
                    $errorCount++;
                    $errorItems[] = [
                        'product' => $productName,
                        'error' => $errorMsg
                    ];
                }
            }

            DB::commit();

            Log::info("✓ BERHASIL: $updatedCount produk di-update");
            Log::info("⊘ SKIP: $skippedCount produk tidak ditemukan");
            Log::info("✗ ERROR: $errorCount produk gagal");

            $resultArray = [
                'success' => true,
                'message' => "Buffer Stock berhasil di-update: $updatedCount produk diubah",
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
                'errors' => $errorCount,
                'updated_items' => $updatedItems,
                'skipped_items' => $skippedItems,
                'error_items' => $errorItems
            ];

            return $this->sanitizeResult($resultArray);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Make sure error message is scalar
            $errorMsg = '';
            try {
                $errorMsg = (string)$e->getMessage();
                if (empty($errorMsg)) {
                    $errorMsg = 'Unknown error occurred';
                }
            } catch (\Throwable $ex) {
                $errorMsg = 'Unable to process error message';
            }

            Log::error("✗ FATAL ERROR: $errorMsg");

            // Clean return array
            $finalResult = [
                'success' => false,
                'message' => $errorMsg,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0
            ];

            return $this->sanitizeResult($finalResult);
        }
    }

    /**
     * Get available CSV files
     */
    public function getAvailableCsvFiles(): array
    {
        $csvFiles = [];
        $searchPaths = [
            base_path('python'),
            base_path(),
            storage_path()
        ];

        foreach ($searchPaths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*.csv');
                foreach ($files as $file) {
                    $csvFiles[basename($file)] = $file;
                }
            }
        }

        return $csvFiles;
    }

    /**
     * Sanitize result array untuk JSON serialization
     * Memastikan tidak ada object atau circular reference
     */
    private function sanitizeResult(array $result): array
    {
        $sanitized = [];

        foreach ($result as $key => $value) {
            if (is_scalar($value)) {
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = array_map(function($item) {
                    if (is_scalar($item)) {
                        return $item;
                    } elseif (is_array($item)) {
                        // Recursive sanitize for nested arrays
                        $cleanItem = [];
                        foreach ($item as $k => $v) {
                            $cleanItem[$k] = is_scalar($v) ? $v : (string)$v;
                        }
                        return $cleanItem;
                    } else {
                        return (string)$item;
                    }
                }, $value);
            } else {
                // Convert object to string
                $sanitized[$key] = (string)$value;
            }
        }

        return $sanitized;
    }
}
