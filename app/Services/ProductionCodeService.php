<?php

namespace App\Services;

use App\Models\ProductionCode;
use App\Models\MasterItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ProductionCodeService
{
    /**
     * Mapping dari nama item atau kategori ke code prefix
     * Bisa dikembangkan untuk baca dari database jika ingin dynamic
     */
    private array $codeMapping = [
        'CNF' => 21078,
        'DS' => 3516,
        'GF' => 5714,
        'TC' => 4637,
        'IB' => 1663,
        'JOY' => 7720,
        'LDR' => 6334,
        'MYB' => 8278,
    ];

    /**
     * Generate production code untuk item tertentu pada branch tertentu
     * Jika hari berubah, counter akan di-reset dan ditambah 1
     * 
     * @param int $itemId - ID item (barang jadi)
     * @param int $branchId - ID branch
     * @param string|null $codePrefix - Optional prefix kode (jika tidak ada, akan di-detect dari item)
     * @return string - Kode produksi lengkap, e.g. "DS3517"
     * @throws \Exception jika prefix tidak dapat dideteksi dan tidak valid
     */
    public function generateProductionCode(int $itemId, int $branchId, ?string $codePrefix = null): string
    {
        $today = Carbon::today();

        // Jika prefix tidak diberikan, coba detect dari item
        if (!$codePrefix) {
            $codePrefix = $this->detectCodePrefixFromItem($itemId);
        }

        // Jika masih tidak ada prefix, throw exception dengan detail
        if (!$codePrefix) {
            $item = MasterItem::find($itemId);
            $itemName = $item ? $item->name_item : 'Unknown';
            $itemCode = $item ? $item->code_item : 'Unknown';
            $availablePrefixes = implode(', ', array_keys($this->codeMapping));
            
            throw new \Exception(
                "Gagal menentukan kode produksi untuk item '{$itemName}' (Code: {$itemCode}).\n" .
                "Prefix yang tersedia: {$availablePrefixes}.\n" .
                "Solusi: Pastikan code_item mengandung salah satu prefix di atas, contoh: 'GB-DS', 'XX-CNF', atau gunakan explicit prefix."
            );
        }

        // Validasi prefix ada di mapping
        if (!isset($this->codeMapping[$codePrefix])) {
            $availablePrefixes = implode(', ', array_keys($this->codeMapping));
            throw new \Exception(
                "Prefix '{$codePrefix}' tidak valid.\n" .
                "Prefix yang tersedia: {$availablePrefixes}"
            );
        }

        // Cari atau buat production code record
        $productionCode = ProductionCode::firstOrCreate(
            ['item_id' => $itemId, 'branch_id' => $branchId],
            [
                'code_prefix' => $codePrefix,
                'current_counter' => $this->codeMapping[$codePrefix],
                'last_used_date' => $today
            ]
        );

        // Jika tanggal berubah (berbeda dengan last_used_date), increment counter
        if ($productionCode->last_used_date->notEqualTo($today)) {
            $productionCode->current_counter += 1;
            $productionCode->last_used_date = $today;
            $productionCode->save();
        }

        // Generate full production code: prefix + counter
        $fullCode = $codePrefix . $productionCode->current_counter;

        return $fullCode;
    }

    /**
     * Detect code prefix dari item berdasarkan item code atau nama
     * Support berbagai format code_item: "GB-DS", "DS-001", "CNF-PRODUCT", "DS", dll
     * 
     * @param int $itemId
     * @return string|null
     */
    private function detectCodePrefixFromItem(int $itemId): ?string
    {
        try {
            $item = MasterItem::find($itemId);
            
            if (!$item) {
                return null;
            }

            // Coba match dari kode item (lebih fleksibel)
            if ($item->code_item) {
                $codeUpper = strtoupper($item->code_item);
                
                // Strategy 1: Cari prefix yang paling panjang dulu di code_item
                // Urutkan mapping berdasarkan length descending untuk match prefix terpanjang dulu
                $sortedPrefixes = array_keys($this->codeMapping);
                usort($sortedPrefixes, function($a, $b) {
                    return strlen($b) - strlen($a);
                });
                
                foreach ($sortedPrefixes as $prefix) {
                    // Match: prefix di awal, atau setelah - atau space
                    // Contoh: "DS", "GB-DS", "DS-001", "DS PRODUCT"
                    if (strpos($codeUpper, $prefix) !== false) {
                        // Validasi: prefix harus standalone (tidak bagian dari kata yang lebih besar)
                        $patterns = [
                            '/^' . $prefix . '[^A-Z0-9]/',           // Awal diikuti non-alphanumeric
                            '/[^A-Z0-9]' . $prefix . '[^A-Z0-9]/',   // Tengah
                            '/[^A-Z0-9]' . $prefix . '$/',            // Akhir
                            '/^' . $prefix . '$/',                    // Exact match
                        ];
                        
                        foreach ($patterns as $pattern) {
                            if (preg_match($pattern, $codeUpper)) {
                                return $prefix;
                            }
                        }
                    }
                }
            }

            // Strategy 2: Coba match dari nama item
            if ($item->name_item) {
                $nameUpper = strtoupper($item->name_item);
                $nameParts = preg_split('/[\s\-_]/', $nameUpper); // Split by space, dash, underscore
                
                foreach ($nameParts as $part) {
                    if (isset($this->codeMapping[$part])) {
                        return $part;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all production codes untuk item
     * 
     * @param int $itemId
     * @return Collection
     */
    public function getProductionCodesForItem(int $itemId): Collection
    {
        return ProductionCode::where('item_id', $itemId)->get();
    }

    /**
     * Get production code info untuk item di branch tertentu
     * 
     * @param int $itemId
     * @param int $branchId
     * @return ProductionCode|null
     */
    public function getProductionCode(int $itemId, int $branchId): ?ProductionCode
    {
        return ProductionCode::where('item_id', $itemId)
            ->where('branch_id', $branchId)
            ->first();
    }

    /**
     * Reset production code counter (jika diperlukan)
     * 
     * @param int $itemId
     * @param int $branchId
     * @param int $newCounter
     * @return ProductionCode|null
     */
    public function resetCounter(int $itemId, int $branchId, int $newCounter): ?ProductionCode
    {
        $productionCode = $this->getProductionCode($itemId, $branchId);
        
        if ($productionCode) {
            $productionCode->current_counter = $newCounter;
            $productionCode->save();
        }

        return $productionCode;
    }

    /**
     * Update code prefix untuk item
     * 
     * @param int $itemId
     * @param int $branchId
     * @param string $newPrefix
     * @return ProductionCode|null
     */
    public function updateCodePrefix(int $itemId, int $branchId, string $newPrefix): ?ProductionCode
    {
        if (!isset($this->codeMapping[$newPrefix])) {
            throw new \Exception("Invalid production code prefix: {$newPrefix}");
        }

        $productionCode = $this->getProductionCode($itemId, $branchId);
        
        if ($productionCode) {
            $productionCode->code_prefix = $newPrefix;
            $productionCode->save();
        }

        return $productionCode;
    }

    /**
     * Get code mapping yang tersedia
     * 
     * @return array
     */
    public function getCodeMapping(): array
    {
        return $this->codeMapping;
    }

    /**
     * Add new code prefix to mapping
     * 
     * @param string $prefix
     * @param int $startNumber
     * @return void
     */
    public function addCodeMapping(string $prefix, int $startNumber): void
    {
        $this->codeMapping[$prefix] = $startNumber;
    }
}
