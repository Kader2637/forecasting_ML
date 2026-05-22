<?php

namespace App\Services;

use App\Models\MasterItem;
use App\Models\MasterItemStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk Calculate dan Update Buffer Stock dengan ROP (Reorder Point)
 * 
 * Formula:
 * ROP = (Avg Daily Demand × Lead Time) + Safety Stock
 * 
 * Dimana:
 * - Avg Daily Demand: Rata-rata penjualan/pemakaian harian
 * - Lead Time: Waktu tunggu supplier (hari)
 * - Safety Stock: Standar deviasi demand × Z-score × √Lead Time
 */
class BufferStockRopCalculationService
{
    private $avgLeadTime = 5.4;    // Default lead time rata-rata (hari)
    private $maxLeadTime = 7;      // Default lead time maksimum (hari)
    private $zScore = 1.65;        // Z-score untuk service level 95%
    private $lookbackDays = 90;    // Lihat data penjualan 90 hari terakhir

    /**
     * Set lead time configuration
     */
    public function setLeadTime(float $avgLeadTime = 5.4, float $maxLeadTime = 7)
    {
        $this->avgLeadTime = $avgLeadTime;
        $this->maxLeadTime = $maxLeadTime;
        return $this;
    }

    /**
     * Calculate ROP untuk semua item
     * 
     * @param int $inventoryId - ID inventori yang akan di-update (default: 1)
     * @return array - Statistics: [updated, skipped, errors]
     */
    public function calculateAndUpdateAllItems(int $inventoryId = 1): array
    {
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        try {
            // Get semua master items
            $items = MasterItem::where('status_item', 'active')->get();

            Log::info("Starting ROP calculation for " . count($items) . " items");

            foreach ($items as $item) {
                try {
                    $result = $this->calculateAndUpdateItemRop($item->item_id, $inventoryId);

                    if ($result === null) {
                        $skipped++;
                    } else {
                        $updated++;
                        Log::info("✓ Updated: {$item->name_item} - ROP: {$result['rop']}");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("✗ Error for item {$item->name_item}: " . $e->getMessage());
                }
            }

            Log::info("ROP calculation completed: Updated=$updated, Skipped=$skipped, Errors=$errors");

            return [
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total' => count($items)
            ];
        } catch (\Exception $e) {
            Log::error("Fatal error in calculateAndUpdateAllItems: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate ROP untuk single item
     * 
     * @param int $itemId - Item ID
     * @param int $inventoryId - Inventory ID
     * @return array|null - ROP data atau null jika skip
     */
    public function calculateAndUpdateItemRop(int $itemId, int $inventoryId = 1): ?array
    {
        // Calculate average daily demand dari sales history
        $avgDailyDemand = $this->getAverageDailyDemand($itemId);
        if ($avgDailyDemand <= 0) {
            // Skip item dengan demand 0
            return null;
        }

        // Calculate safety stock
        $safetyStock = $this->calculateSafetyStock($itemId);

        // Calculate ROP = (Avg Daily Demand × Lead Time) + Safety Stock
        $rop = ($avgDailyDemand * $this->avgLeadTime) + $safetyStock;
        $rop = (int) round($rop, 0);

        // Update atau create master_items_stock
        $this->updateItemStock($itemId, $inventoryId, $rop);

        return [
            'item_id' => $itemId,
            'avg_daily_demand' => round($avgDailyDemand, 2),
            'safety_stock' => round($safetyStock, 2),
            'rop' => $rop
        ];
    }

    /**
     * Get average daily demand dari sales history
     * 
     * @param int $itemId
     * @return float
     */
    private function getAverageDailyDemand(int $itemId): float
    {
        try {
            // Get dari TransactionSalesDetails (penjualan)
            $result = DB::table('transaction_sales_details as tsd')
                ->join('transaction_sales as ts', 'ts.transaction_id', '=', 'tsd.transaction_id')
                ->where('tsd.item_id', $itemId)
                ->where('ts.date', '>=', now()->subDays($this->lookbackDays))
                ->selectRaw('COUNT(DISTINCT DATE(ts.date)) as total_days, SUM(tsd.qty) as total_qty')
                ->first();

            if (!$result || $result->total_days == 0) {
                // Fallback ke FinishedGoodsOut jika ada
                $result = DB::table('finished_goods_out')
                    ->where('item_id', $itemId)
                    ->where('out_date', '>=', now()->subDays($this->lookbackDays))
                    ->selectRaw('COUNT(DISTINCT DATE(out_date)) as total_days, SUM(total_sold) as total_qty')
                    ->first();

                if (!$result || $result->total_days == 0) {
                    return 0;
                }
            }

            return $result->total_qty / max($result->total_days, 1);
        } catch (\Exception $e) {
            Log::warning("Could not calculate demand for item $itemId: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate safety stock menggunakan statistical method
     * Safety Stock = Z-score × Standard Deviation × √Lead Time
     * 
     * @param int $itemId
     * @return float
     */
    private function calculateSafetyStock(int $itemId): float
    {
        try {
            // Get daily sales dari sales history
            $dailySales = DB::table('transaction_sales_details as tsd')
                ->join('transaction_sales as ts', 'ts.transaction_id', '=', 'tsd.transaction_id')
                ->where('tsd.item_id', $itemId)
                ->where('ts.date', '>=', now()->subDays($this->lookbackDays))
                ->selectRaw('DATE(ts.date) as sale_date, SUM(tsd.qty) as daily_qty')
                ->groupBy('sale_date')
                ->pluck('daily_qty')
                ->toArray();

            if (empty($dailySales)) {
                // Fallback ke FinishedGoodsOut
                $dailySales = DB::table('finished_goods_out')
                    ->where('item_id', $itemId)
                    ->where('out_date', '>=', now()->subDays($this->lookbackDays))
                    ->selectRaw('DATE(out_date) as out_date, SUM(total_sold) as daily_qty')
                    ->groupBy('out_date')
                    ->pluck('daily_qty')
                    ->toArray();

                if (empty($dailySales)) {
                    return 0;
                }
            }

            // Calculate standard deviation
            $mean = array_sum($dailySales) / count($dailySales);
            $variance = 0;
            foreach ($dailySales as $sale) {
                $variance += pow($sale - $mean, 2);
            }
            $stdDev = sqrt($variance / count($dailySales));

            // Safety Stock = Z × σ × √L
            $safetyStock = $this->zScore * $stdDev * sqrt($this->avgLeadTime);

            return $safetyStock;
        } catch (\Exception $e) {
            Log::warning("Could not calculate safety stock for item $itemId: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update atau create record di master_items_stock
     * 
     * @param int $itemId
     * @param int $inventoryId
     * @param int $rop
     */
    private function updateItemStock(int $itemId, int $inventoryId, int $rop): void
    {
        $itemStock = MasterItemStock::where('item_id', $itemId)
            ->where('inventory_id', $inventoryId)
            ->first();

        if ($itemStock) {
            // Update existing
            $itemStock->update(['buffer_stock' => $rop]);
        } else {
            // Create new
            MasterItemStock::create([
                'item_id' => $itemId,
                'inventory_id' => $inventoryId,
                'stock' => 0,
                'buffer_stock' => $rop
            ]);
        }
    }

    /**
     * Get summary statistics
     */
    public function getSummaryStatistics(int $inventoryId = 1): array
    {
        $stocks = MasterItemStock::with('item')
            ->where('inventory_id', $inventoryId)
            ->where('buffer_stock', '>', 0)
            ->get();

        $totalRop = $stocks->sum('buffer_stock');
        $avgRop = $stocks->avg('buffer_stock');
        $needsOrder = $stocks->where('stock', '<=', DB::raw('buffer_stock'))->count();

        return [
            'total_items' => $stocks->count(),
            'total_rop_sum' => (int) $totalRop,
            'avg_rop' => round($avgRop, 2),
            'items_needs_order' => $needsOrder,
            'items_safe' => $stocks->count() - $needsOrder,
        ];
    }
}
