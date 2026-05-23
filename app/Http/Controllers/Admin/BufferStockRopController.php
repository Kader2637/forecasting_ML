<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BufferStockRopCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BufferStockRopController extends Controller
{
    protected $ropService;

    public function __construct()
    {
        $this->ropService = new BufferStockRopCalculationService();
    }

    /**
     * POST: /admin/inventory/buffer-stock/calculate-rop
     * Calculate dan update buffer stock dengan ROP
     */
    public function calculateRop(Request $request)
    {
        try {
            // Get inventory ID dari request (default: 1)
            $inventoryId = $request->get('inventory_id', 1);

            Log::info("Starting ROP calculation via API for inventory_id=$inventoryId");

            // Run calculation
            $result = $this->ropService->calculateAndUpdateAllItems($inventoryId);

            // Get summary statistics
            $stats = $this->ropService->getSummaryStatistics($inventoryId);

            return response()->json([
                'success' => true,
                'message' => 'Buffer stock calculation completed successfully',
                'data' => [
                    'calculation_result' => $result,
                    'summary' => $stats,
                    'timestamp' => now()->toDateTimeString()
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error calculating ROP: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error calculating buffer stock: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/rop-summary
     * Get summary statistics
     */
    public function getRopSummary(Request $request)
    {
        try {
            $inventoryId = $request->get('inventory_id', 1);
            $stats = $this->ropService->getSummaryStatistics($inventoryId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
