<?php

namespace App\Services;

use App\Models\MasterItemRawMaterial;
use App\Models\MasterItemBillOfMaterials;
use App\Models\ProductionOrder;
use App\Models\RawMaterialIn;
use App\Models\RawMaterialOut;
use App\Models\FinishedGoodsIn;
use App\Models\FinishedGoodsOut;
use App\Models\BufferStockConfig;
use App\Models\StockAdjustment;
use App\Models\MasterItemStock;
use Carbon\Carbon;

class BufferStockCalculationService
{
    /**
     * Calculate buffer stock for a raw material
     *
     * @param int $itemRawId
     * @param int $lookbackDays Default 90 days for trend analysis
     * @return array
     */
    public function calculateBufferStock($itemRawId, $lookbackDays = 90)
    {
        $material = MasterItemRawMaterial::find($itemRawId);
        if (!$material) {
            return ['error' => 'Material not found'];
        }

        $config = BufferStockConfig::active()->byMaterialType($material->material_name)->first()
            ?? BufferStockConfig::active()->first();

        $avgDailyUsage = $this->calculateAverageDailyUsage($itemRawId, $lookbackDays);
        $usageVariability = $this->calculateUsageVariability($itemRawId, $lookbackDays, $avgDailyUsage);
        $leadTime = $config->lead_time_days ?? 7;
        $safetyDay = $config->safety_days ?? 3;
        $variabilityFactor = $config->demand_variability_factor ?? 1.65;

        // Safety Stock = Variability Factor × Safety Factor × StdDev of Demand × √Lead Time
        $safetyStock = $variabilityFactor * $usageVariability * sqrt($leadTime);

        // Reorder Point = (Avg Daily Usage × Lead Time) + Safety Stock
        $reorderPoint = ($avgDailyUsage * $leadTime) + $safetyStock;

        // Buffer Stock = (Avg Daily Usage × Safety Days) + Safety Stock
        $bufferStock = ($avgDailyUsage * $safetyDay) + $safetyStock;

        // Maximum Stock = Reorder Point + Economic Order Quantity (simplified)
        $eoq = $this->calculateEconomicOrderQuantity($itemRawId);
        $maxStock = $reorderPoint + $eoq;

        return [
            'item_raw_id' => $itemRawId,
            'material_name' => $material->material_name,
            'current_stock' => $material->current_stock,
            'avg_daily_usage' => round($avgDailyUsage, 2),
            'usage_variability' => round($usageVariability, 2),
            'lead_time_days' => $leadTime,
            'safety_days' => $safetyDay,
            'safety_stock' => round($safetyStock, 2),
            'reorder_point' => round($reorderPoint, 2),
            'buffer_stock' => round($bufferStock, 2),
            'max_stock' => round($maxStock, 2),
            'min_reorder_qty' => $config->min_reorder_quantity ?? 100,
            'stock_status' => $this->determineStockStatus($material->current_stock, $reorderPoint, $bufferStock, $maxStock),
            'recommendation' => $this->getStockRecommendation($material->current_stock, $reorderPoint, $bufferStock)
        ];
    }

    /**
     * Calculate average daily usage for a material
     *
     * @param int $itemRawId
     * @param int $lookbackDays
     * @return float
     */
    private function calculateAverageDailyUsage($itemRawId, $lookbackDays)
    {
        $startDate = Carbon::now()->subDays($lookbackDays);

        $totalUsed = RawMaterialOut::where('item_raw_id', $itemRawId)
            ->where('issued_date', '>=', $startDate)
            ->sum('qty_issued');

        if ($totalUsed == 0 || $lookbackDays == 0) {
            return $this->estimateFromBillOfMaterials($itemRawId);
        }

        return $totalUsed / $lookbackDays;
    }

    /**
     * Estimate average daily usage from bill of materials
     *
     * @param int $itemRawId
     * @return float
     */
    private function estimateFromBillOfMaterials($itemRawId)
    {
        $bomRecords = MasterItemBillOfMaterials::where('item_raw_id', $itemRawId)->get();
        
        if ($bomRecords->isEmpty()) {
            return 0;
        }

        $totalDailyUsage = 0;

        foreach ($bomRecords as $bom) {
            // Get average production per day for this item
            $productionOrders = ProductionOrder::where('item_id', $bom->item_id)
                ->whereDate('planned_date', '>=', Carbon::now()->subDays(90))
                ->get();

            $avgProduction = $productionOrders->avg('qty_planned') ?? 0;
            $requiredQty = ($avgProduction * $bom->quantity_required) / 100 * (100 - $bom->yield_percentage);
            
            $totalDailyUsage += $requiredQty;
        }

        return $totalDailyUsage / 90;
    }

    /**
     * Calculate the variability (standard deviation) of usage
     *
     * @param int $itemRawId
     * @param int $lookbackDays
     * @param float $avgDailyUsage
     * @return float
     */
    private function calculateUsageVariability($itemRawId, $lookbackDays, $avgDailyUsage)
    {
        $usageData = RawMaterialOut::where('item_raw_id', $itemRawId)
            ->where('issued_date', '>=', Carbon::now()->subDays($lookbackDays))
            ->selectRaw('DATE(issued_date) as date, SUM(qty_issued) as daily_usage')
            ->groupBy('date')
            ->pluck('daily_usage')
            ->toArray();

        if (empty($usageData)) {
            return $avgDailyUsage * 0.3; // Estimate 30% variability if no data
        }

        // Calculate standard deviation
        $mean = array_sum($usageData) / count($usageData);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $usageData)) / count($usageData);

        return sqrt($variance) ?: $avgDailyUsage * 0.3;
    }

    /**
     * Calculate Economic Order Quantity
     * EOQ = √(2DS/H) where D=annual demand, S=order cost, H=holding cost
     *
     * @param int $itemRawId
     * @return float
     */
    private function calculateEconomicOrderQuantity($itemRawId)
    {
        $material = MasterItemRawMaterial::find($itemRawId);
        
        // Annual usage (30 days back extrapolated to year)
        $monthlyUsage = RawMaterialOut::where('item_raw_id', $itemRawId)
            ->where('issued_date', '>=', Carbon::now()->subDays(30))
            ->sum('qty_issued');
        
        $annualDemand = $monthlyUsage * 12;
        
        // Estimated ordering cost per order (simplified: 50000)
        $orderCost = 50000;
        
        // Holding cost = 20% of material cost per unit per year
        $holdingCost = ($material->purchase_price ?? 10000) * 0.20;

        if ($holdingCost <= 0 || $annualDemand <= 0) {
            return 100; // Minimum EOQ
        }

        $eoq = sqrt((2 * $annualDemand * $orderCost) / $holdingCost);
        
        return max($eoq, 100); // Minimum 100 units
    }

    /**
     * Determine stock status based on current stock and thresholds
     *
     * @param float $currentStock
     * @param float $reorderPoint
     * @param float $bufferStock
     * @param float $maxStock
     * @return string
     */
    private function determineStockStatus($currentStock, $reorderPoint, $bufferStock, $maxStock)
    {
        if ($currentStock <= 0) {
            return 'out_of_stock';
        } elseif ($currentStock <= $bufferStock) {
            return 'critical';
        } elseif ($currentStock <= $reorderPoint) {
            return 'low';
        } elseif ($currentStock >= $maxStock) {
            return 'overstock';
        } else {
            return 'normal';
        }
    }

    /**
     * Get recommendation for ordering
     *
     * @param float $currentStock
     * @param float $reorderPoint
     * @param float $bufferStock
     * @return string
     */
    private function getStockRecommendation($currentStock, $reorderPoint, $bufferStock)
    {
        if ($currentStock <= $bufferStock) {
            return 'Order immediately - stock critical';
        } elseif ($currentStock <= $reorderPoint) {
            return 'Order soon - stock below reorder point';
        } else {
            return 'Stock sufficient - no action needed';
        }
    }

    /**
     * Calculate buffer stock for all raw materials
     *
     * @param int $lookbackDays
     * @return array
     */
    public function calculateAllBufferStocks($lookbackDays = 90)
    {
        $materials = MasterItemRawMaterial::all();
        $results = [];

        foreach ($materials as $material) {
            $results[] = $this->calculateBufferStock($material->item_raw_id, $lookbackDays);
        }

        return $results;
    }

    /**
     * Update buffer stock in database for a raw material
     *
     * @param int $itemRawId
     * @return bool
     */
    public function updateMaterialBufferStock($itemRawId)
    {
        $calculation = $this->calculateBufferStock($itemRawId);
        
        if (isset($calculation['error'])) {
            return false;
        }

        MasterItemRawMaterial::find($itemRawId)->update([
            'buffer_stock' => $calculation['buffer_stock'],
            'reorder_point' => $calculation['reorder_point'],
            'avg_daily_usage' => $calculation['avg_daily_usage'],
            'stock_status' => $calculation['stock_status']
        ]);

        return true;
    }

    /**
     * Sync all buffer stocks to database
     *
     * @return array
     */
    public function syncAllBufferStocks()
    {
        $results = $this->calculateAllBufferStocks();
        $successCount = 0;

        foreach ($results as $result) {
            if (!isset($result['error']) && $this->updateMaterialBufferStock($result['item_raw_id'])) {
                $successCount++;
            }
        }

        return [
            'total_materials' => count($results),
            'updated' => $successCount,
            'details' => $results
        ];
    }

    /**
     * Get finished goods demand forecasting
     *
     * @param int $itemId
     * @param int $forecastDays
     * @return array
     */
    public function getForecastDemand($itemId, $forecastDays = 30)
    {
        $startDate = Carbon::now()->subDays(90);
        
        // Get historical sales
        $historicalSales = FinishedGoodsOut::where('item_id', $itemId)
            ->where('out_date', '>=', $startDate)
            ->selectRaw('DATE(out_date) as date, SUM(qty_out) as daily_sales')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        if ($historicalSales->isEmpty()) {
            return [
                'item_id' => $itemId,
                'forecast_days' => $forecastDays,
                'daily_average' => 0,
                'daily_variance' => 0,
                'forecast_total' => 0,
                'confidence_interval' => [
                    'low' => 0,
                    'high' => 0
                ]
            ];
        }

        $salesData = $historicalSales->pluck('daily_sales')->toArray();
        $avgDaily = array_sum($salesData) / count($salesData);
        
        // Calculate variance
        $variance = array_sum(array_map(function($x) use ($avgDaily) {
            return pow($x - $avgDaily, 2);
        }, $salesData)) / count($salesData);
        
        $stdDev = sqrt($variance);

        return [
            'item_id' => $itemId,
            'forecast_days' => $forecastDays,
            'daily_average' => round($avgDaily, 2),
            'daily_variance' => round($stdDev, 2),
            'forecast_total' => round($avgDaily * $forecastDays, 0),
            'confidence_interval' => [
                'low' => round(max(0, ($avgDaily - (1.96 * $stdDev)) * $forecastDays), 0),
                'high' => round(($avgDaily + (1.96 * $stdDev)) * $forecastDays, 0)
            ]
        ];
    }

    /**
     * Calculate stock adjustment impact
     *
     * @param int $itemRawId
     * @param int $daysBack
     * @return array
     */
    public function getStockAdjustmentAnalysis($itemRawId, $daysBack = 30)
    {
        $startDate = Carbon::now()->subDays($daysBack);
        
        $adjustments = StockAdjustment::where('item_type', 'raw_material')
            ->where('item_id', $itemRawId)
            ->where('adjusted_at', '>=', $startDate)
            ->get();

        $totalAdjustment = $adjustments->sum('qty_difference');
        
        return [
            'item_raw_id' => $itemRawId,
            'period_days' => $daysBack,
            'total_adjustments' => $adjustments->count(),
            'total_adjustment_qty' => $totalAdjustment,
            'average_adjustment' => $adjustments->count() > 0 ? round($totalAdjustment / $adjustments->count(), 2) : 0,
            'adjustment_types' => $adjustments->groupBy('adjustment_type')
                ->map(fn($group) => [
                    'count' => $group->count(),
                    'total_qty' => $group->sum('qty_difference')
                ])->toArray()
        ];
    }
}
