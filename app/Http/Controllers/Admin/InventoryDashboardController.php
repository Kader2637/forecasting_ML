<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterInventory;
use App\Models\MasterItem;
use App\Models\MasterBranch;
use App\Models\MasterItemBillOfMaterials;
use App\Models\MasterItemStock;
use App\Models\MasterItemRawMaterial;
use App\Models\ProductionOrder;
use App\Models\RawMaterialIn;
use App\Models\RawMaterialOut;
use App\Models\FinishedGoodsIn;
use App\Models\FinishedGoodsOut;
use App\Models\BufferStockConfig;
use App\Models\StockAdjustment;
use App\Models\TransactionPurchase;
use App\Models\TransactionSalesDetails;
use App\Services\BufferStockCalculationService;
use App\Services\InventoryAnalysisService;
use App\Services\ProductionCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class InventoryDashboardController extends Controller
{
    public function index()
    {
        // Total inventori dan item
        $totalInventories = MasterInventory::count();
        $totalItems       = MasterItem::count();
        $totalStock       = MasterItemStock::sum('stock');
        $lowStockItems    = MasterItemStock::where('stock', '<', 10)->where('stock', '>', 0)->count();
        $emptyStockItems  = MasterItemStock::where('stock', 0)->count();

        // Stok Masuk bulan ini (dari pembelian)
        $stockMasukBulanIni = TransactionPurchase::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
        $nilaiMasukBulanIni = TransactionPurchase::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('total_amount');

        // Stok Keluar bulan ini (dari penjualan)
        $stockKeluarBulanIni = TransactionSalesDetails::whereHas('transactionSales', function ($q) {
                $q->whereMonth('date', now()->month)->whereYear('date', now()->year);
            })->sum('qty');
        $nilaiKeluarBulanIni = TransactionSalesDetails::whereHas('transactionSales', function ($q) {
                $q->whereMonth('date', now()->month)->whereYear('date', now()->year);
            })->sum('total_amount');

        // Data trend 6 bulan terakhir untuk grafik
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyData[] = [
                'month'   => $date->format('M Y'),
                'masuk'   => TransactionPurchase::whereMonth('date', $date->month)
                    ->whereYear('date', $date->year)
                    ->count(),
                'nilai_masuk' => TransactionPurchase::whereMonth('date', $date->month)
                    ->whereYear('date', $date->year)
                    ->sum('total_amount'),
                'keluar'  => TransactionSalesDetails::whereHas('transactionSales', function ($q) use ($date) {
                        $q->whereMonth('date', $date->month)->whereYear('date', $date->year);
                    })->sum('qty'),
                'nilai_keluar' => TransactionSalesDetails::whereHas('transactionSales', function ($q) use ($date) {
                        $q->whereMonth('date', $date->month)->whereYear('date', $date->year);
                    })->sum('total_amount'),
            ];
        }

        // Transaksi pembelian terbaru (stok masuk)
        $recentMasuk = TransactionPurchase::orderBy('date', 'desc')
            ->limit(5)
            ->get();

        // Transaksi penjualan terbaru per item (stok keluar)
        $recentKeluar = TransactionSalesDetails::with(['masterItem', 'transactionSales'])
            ->whereHas('transactionSales')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Daftar item dengan stok saat ini
        $itemStocks = MasterItemStock::with(['item', 'inventory'])
            ->orderBy('stock', 'asc')
            ->get();

        return view('admin_inventory.dashboard', compact(
            'totalInventories', 'totalItems', 'totalStock', 'lowStockItems', 'emptyStockItems',
            'stockMasukBulanIni', 'nilaiMasukBulanIni',
            'stockKeluarBulanIni', 'nilaiKeluarBulanIni',
            'monthlyData', 'recentMasuk', 'recentKeluar', 'itemStocks'
        ));
    }

    public function rawMaterials(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search = $request->get('search', '');

        // Query builder untuk raw materials dengan item dan inventory
        $query = MasterItemStock::with(['item.category', 'inventory']);

        // Filter berdasarkan search
        if ($search) {
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name_item', 'like', "%{$search}%")
                    ->orWhere('code_item', 'like', "%{$search}%");
            })->orWhereHas('inventory', function ($q) use ($search) {
                $q->where('name_inventory', 'like', "%{$search}%");
            });
        }

        // Get paginated data
        $itemStocks = $query->paginate($perPage);

        // Format data untuk view
        $rawMaterials = $itemStocks->through(function ($itemStock) {
            $stock = $itemStock->stock;
            $bufferStock = (int) $itemStock->buffer_stock;
            $stockDifference = $stock - $bufferStock;
            $needsOrder = $stockDifference < 0;

            return [
                'item_stock_id' => $itemStock->item_stock_id,
                'name_item' => $itemStock->item->name_item ?? '-',
                'code_item' => $itemStock->item->code_item ?? '',
                'inventory' => $itemStock->inventory->name_inventory ?? '-',
                'category' => $itemStock->item->category->name_category ?? '-',
                'stock' => $stock,
                'buffer_stock' => $bufferStock,
                'stock_difference' => abs($stockDifference),
                'needs_order' => $needsOrder
            ];
        });

        // Hitung summary data
        $allItemStocks = MasterItemStock::all();
        $summary = [
            'total' => $allItemStocks->count(),
            'needs_order' => $allItemStocks->filter(function ($item) {
                return ($item->stock - (int) $item->buffer_stock) < 0;
            })->count(),
            'sufficient' => $allItemStocks->filter(function ($item) {
                return ($item->stock - (int) $item->buffer_stock) >= 0;
            })->count(),
        ];

        return view('admin_inventory.finished_goods', compact(
            'rawMaterials',
            'summary',
            'perPage',
            'search'
        ));
    }

    public function getRawMaterialDetail($itemStockId)
    {
        $itemStock = MasterItemStock::with(['item.category', 'inventory'])->find($itemStockId);

        if (!$itemStock) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        $stock = $itemStock->stock;
        $bufferStock = (int) $itemStock->buffer_stock;
        $stockDifference = $stock - $bufferStock;
        $needsOrder = $stockDifference < 0;

        return response()->json([
            'item_stock_id' => $itemStock->item_stock_id,
            'name_item' => $itemStock->item->name_item ?? '-',
            'code_item' => $itemStock->item->code_item ?? '',
            'inventory' => $itemStock->inventory->name_inventory ?? '-',
            'category' => $itemStock->item->category->name_category ?? '-',
            'stock' => $stock,
            'buffer_stock' => $bufferStock,
            'stock_difference' => abs($stockDifference),
            'needs_order' => $needsOrder
        ]);
    }

    public function finishedGoodsShow($itemStockId)
    {
        $itemStock = MasterItemStock::with(['item.category', 'inventory'])->find($itemStockId);

        if (!$itemStock) {
            abort(404, 'Data tidak ditemukan');
        }

        $stock = $itemStock->stock;
        $bufferStock = (int) $itemStock->buffer_stock;
        $stockDifference = abs($stock - $bufferStock);

        return view('admin_inventory.finished_goods_show', compact(
            'itemStock',
            'bufferStock',
            'stockDifference'
        ));
    }

    public function finishedGoodsEdit($itemStockId)
    {
        $itemStock = MasterItemStock::with(['item.category', 'inventory'])->find($itemStockId);

        if (!$itemStock) {
            abort(404, 'Data tidak ditemukan');
        }

        $bufferStock = (int) $itemStock->buffer_stock;

        return view('admin_inventory.finished_goods_edit', compact(
            'itemStock',
            'bufferStock'
        ));
    }

    public function updateRawMaterial(Request $request, $itemStockId)
    {
        $request->validate([
            'stock' => 'required|integer|min:0|max:9999999'
        ]);

        $itemStock = MasterItemStock::find($itemStockId);

        if (!$itemStock) {
            return response()->json(['error' => 'Data tidak ditemukan', 'success' => false], 404);
        }

        $itemStock->update([
            'stock' => $request->stock
        ]);

        if (!$request->expectsJson()) {
            return redirect()
                ->route('admin.inventory.finished-goods.show', $itemStockId)
                ->with('success', 'Stok berhasil diperbarui');
        }

        return response()->json([
            'success' => true,
            'message' => 'Stok berhasil diperbarui'
        ]);
    }

    public function destroyFinishedGoods($itemStockId)
    {
        $itemStock = MasterItemStock::with('item')->find($itemStockId);

        if (!$itemStock) {
            return response()->json([
                'success' => false,
                'message' => 'Data produk jadi tidak ditemukan.'
            ], 404);
        }

        $itemName = $itemStock->item->name_item ?? 'Produk';
        $itemStock->delete();

        return response()->json([
            'success' => true,
            'message' => $itemName . ' berhasil dihapus.'
        ]);
    }

    /**
     * GET: /admin/inventory/finished-goods/create/form-data
     * Get items and inventories for create form
     */
    public function getFinishedGoodsFormData()
    {
        try {
            $items = MasterItem::select('item_id', 'name_item', 'code_item')
                ->where('status_item', 'active')
                ->orderBy('name_item')
                ->get();

            $inventories = MasterInventory::select('inventory_id', 'name_inventory')
                ->where('status_inventory', 'active')
                ->orderBy('name_inventory')
                ->get();

            return response()->json([
                'success' => true,
                'items' => $items,
                'inventories' => $inventories
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting form data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data form'
            ], 500);
        }
    }

    /**
     * POST: /admin/inventory/finished-goods
     * Store new finished goods
     */
    public function storeFinishedGoods(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_id' => 'required|integer|exists:master_items,item_id',
                'inventory_id' => 'required|integer|exists:master_inventories,inventory_id',
                'stock' => 'required|integer|min:0|max:9999999',
                'buffer_stock' => 'required|integer|min:0|max:9999999'
            ]);

            Log::info('Creating finished goods', [
                'item_id' => $validated['item_id'],
                'inventory_id' => $validated['inventory_id'],
                'stock' => $validated['stock'],
                'buffer_stock' => $validated['buffer_stock'],
            ]);

            // Check if already exists
            $existing = MasterItemStock::where('item_id', $validated['item_id'])
                ->where('inventory_id', $validated['inventory_id'])
                ->first();

            if ($existing) {
                Log::warning('Finished goods already exists', [
                    'item_id' => $validated['item_id'],
                    'inventory_id' => $validated['inventory_id'],
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kombinasi item dan inventori sudah ada',
                        'errors' => ['item_id' => ['Kombinasi ini sudah terdaftar']]
                    ], 422);
                }

                return redirect()
                    ->back()
                    ->with('error', 'Kombinasi item dan inventori sudah ada');
            }

            $itemStock = MasterItemStock::create([
                'item_id' => $validated['item_id'],
                'inventory_id' => $validated['inventory_id'],
                'stock' => $validated['stock'],
                'buffer_stock' => $validated['buffer_stock']
            ]);

            Log::info('Finished goods created successfully', [
                'item_stock_id' => $itemStock->item_stock_id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Produk jadi berhasil ditambahkan',
                    'data' => $itemStock
                ], 201);
            }

            return redirect()
                ->route('admin.inventory.finished-goods')
                ->with('success', 'Produk jadi berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in storeFinishedGoods', [
                'errors' => $e->errors(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error storing finished goods: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan data produk jadi: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Gagal menyimpan data produk jadi');
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/raw-materials
     * Display raw materials with buffer stock calculations from CSV analysis
     */
    public function bufferStockRawMaterials(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search = $request->get('search', '');

        try {
            // Query database directly for raw materials
            $query = MasterItemRawMaterial::query();

            // Apply search filter
            if ($search !== '') {
                $keyword = $search;
                $query->where(function ($q) use ($keyword) {
                    $q->where('item_raw_id', 'like', "%{$keyword}%")
                        ->orWhere('material_name', 'like', "%{$keyword}%")
                        ->orWhere('unit', 'like', "%{$keyword}%")
                        ->orWhere('supplier_name', 'like', "%{$keyword}%");
                });
            }

            // Get paginated data
            $materialData = $query->orderBy('material_name', 'asc')->paginate($perPage);

            // Calculate summary statistics
            $allMaterials = MasterItemRawMaterial::all();
            $summary = [
                'total_materials' => $allMaterials->count(),
                'total_inventory_value' => $allMaterials->sum(fn($m) => ($m->current_stock ?? 0) * ($m->purchase_price ?? 0)),
                'avg_buffer_stock' => round($allMaterials->avg('buffer_stock') ?? 0, 2),
                'avg_lead_time' => round($allMaterials->avg('lead_time_days') ?? 0, 2),
                'empty_stock' => $allMaterials->filter(fn($m) => ($m->current_stock ?? 0) <= 0)->count(),
                'items_below_buffer' => $allMaterials->filter(fn($m) => ($m->current_stock ?? 0) < ($m->buffer_stock ?? 0))->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Buffer Stock Raw Materials Error: ' . $e->getMessage());
            return back()->with('error', 'Error membaca data bahan baku: ' . $e->getMessage());
        }

        return view('admin_inventory.buffer_stock_raw_materials', compact(
            'materialData',
            'summary',
            'perPage',
            'search'
        ));
    }

    /**
     * GET: /admin/inventory/forecasting/demand
     * Display demand forecasting for finished goods
     */
    public function demandForecasting(Request $request)
    {
        $summaryTable = 'arima_forecast_summaries';
        $categoryTable = 'arima_forecast_mae_category_summaries';

        if (DB::getSchemaBuilder()->hasTable($summaryTable) && DB::getSchemaBuilder()->hasTable($categoryTable)) {
            $masterItemTable = (new MasterItem())->getTable();

            $rawForecastData = DB::table($summaryTable . ' as afs')
                ->leftJoin($masterItemTable . ' as mi', 'mi.code_item', '=', 'afs.produk')
                ->select([
                    'afs.produk',
                    'afs.arima_order',
                    'afs.mae',
                    'afs.rmse',
                    'afs.mape_percentage',
                    'afs.stationary',
                    'afs.adf_p_value',
                    'afs.kategori_mae',
                    'afs.updated_at',
                    'mi.item_id',
                    'mi.code_item',
                    'mi.name_item',
                    'mi.costprice_item',
                    'mi.sellingprice_item',
                    'mi.current_inventory',
                ])
                ->orderByRaw("CASE afs.kategori_mae WHEN 'rendah' THEN 1 WHEN 'menengah' THEN 2 WHEN 'tinggi' THEN 3 ELSE 4 END")
                ->orderBy('afs.mae', 'asc')
                ->get();

            $forecastData = $rawForecastData->map(function ($row) {
                return [
                    'item_id' => $row->item_id,
                    'code_item' => $row->code_item ?: $row->produk,
                    'name_item' => $row->name_item ?: $row->produk,
                    'costprice_item' => $row->costprice_item ?? 0,
                    'sellingprice_item' => $row->sellingprice_item ?? 0,
                    'current_inventory' => $row->current_inventory ?? 0,
                    'produk' => $row->produk,
                    'arima_order' => $row->arima_order,
                    'mae' => (float) $row->mae,
                    'rmse' => (float) $row->rmse,
                    'mape_percentage' => (float) $row->mape_percentage,
                    'stationary' => is_null($row->stationary) ? '-' : ($row->stationary ? 'Ya' : 'Tidak'),
                    'adf_p_value' => is_null($row->adf_p_value) ? null : (float) $row->adf_p_value,
                    'kategori_mae' => $row->kategori_mae,
                    'synced_at' => $row->updated_at,
                ];
            });
        } else {
            $forecastData = collect();
        }

        return view('admin_inventory.demand_forecasting', compact(
            'forecastData'
        ));
    }

    /**
     * GET: /admin/inventory/forecasting/demand-detail/{produk}
     * Get detail forecast data untuk produk spesifik
     */
    public function getDemandForecastDetail(Request $request, $produk)
    {
        $summaryTable = 'arima_forecast_summaries';

        // Get summary data untuk produk
        $summary = DB::table($summaryTable)
            ->where('produk', $produk)
            ->first();

        if (!$summary) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }

        // Get detail data
        $detailData = DB::table('arima_forecast_details')
            ->where('produk', $produk)
            ->orderBy('date', 'asc')
            ->get();

        // Group by data type
        $trainingData = $detailData->where('data_type', 'training')->values();
        $actualData = $detailData->where('data_type', 'actual')->values();
        $forecastData = $detailData->where('data_type', 'forecast')->values();

        // Get master item info
        $masterItem = DB::table((new MasterItem())->getTable())
            ->where('code_item', $produk)
            ->first();

        return response()->json([
            'success' => true,
            'summary' => [
                'produk' => $summary->produk,
                'name_item' => $masterItem?->name_item ?? $produk,
                'mae' => (float) $summary->mae,
                'rmse' => (float) $summary->rmse,
                'mape_percentage' => (float) $summary->mape_percentage,
                'arima_order' => $summary->arima_order,
                'kategori_mae' => $summary->kategori_mae,
                'stationary' => $summary->stationary,
                'adf_p_value' => $summary->adf_p_value,
            ],
            'chart_data' => [
                'training' => $trainingData->map(fn($d) => [
                    'date' => $d->date,
                    'value' => (float) $d->actual_sales,
                ])->toArray(),
                'actual' => $actualData->map(fn($d) => [
                    'date' => $d->date,
                    'actual' => (float) $d->actual_sales,
                    'predicted' => (float) $d->predicted_sales,
                    'error' => (float) $d->error,
                ])->toArray(),
                'forecast' => $forecastData->map(fn($d) => [
                    'date' => $d->date,
                    'predicted' => (float) $d->predicted_sales,
                ])->toArray(),
            ],
            'table_data' => $actualData->map(fn($d) => [
                'date' => $d->date,
                'actual_sales' => (float) $d->actual_sales,
                'predicted_sales' => (float) $d->predicted_sales,
                'error' => (float) $d->error,
                'absolute_error' => (float) $d->absolute_error,
            ])->toArray(),
        ]);
    }

    /**
     * GET: /admin/inventory/stock-opname
     * Display stock opname and adjustment history
     */
    public function stockOpname(Request $request)
    {
        // Get Gentle Living branch
        $gentleLivingBranch = MasterBranch::where('name_branch', 'like', '%Gentle Living%')->first();
        
        if (!$gentleLivingBranch) {
            $gentleLivingBranch = MasterBranch::first(); // Fallback to first branch
        }

        // Get all inventories for Gentle Living branch
        $inventories = MasterInventory::where('branch_id', $gentleLivingBranch->branch_id)
            ->pluck('inventory_id')
            ->toArray();

        // Get all items with their stock data from latest adjustment
        $allItems = MasterItem::with('itemStocks')
            ->where('status_item', 'active')
            ->get();

        // Prepare stock comparison data
        $stockComparison = [];
        
        foreach ($allItems as $item) {
            // Get latest adjustment for this item in Gentle Living inventories
            $latestAdjustment = StockAdjustment::where('item_id', $item->item_id)
                ->whereIn('inventory_id', $inventories)
                ->orderBy('adjusted_at', 'desc')
                ->first();

            // Get current system stock from MasterItemStock
            $itemStock = MasterItemStock::where('item_id', $item->item_id)
                ->whereIn('inventory_id', $inventories)
                ->first();

            // Skip if no inventory record exists yet
            if (!$itemStock) {
                continue;
            }

            // Build comparison data
            $qtySystem = $itemStock->stock ?? 0;
            $qtyPhysical = $latestAdjustment->qty_physical ?? 0;
            $qtyDifference = $qtyPhysical - $qtySystem;

            // Determine item type - check if it's a raw material or finished good
            $itemType = 'finished_good'; // default
            if ($latestAdjustment && $latestAdjustment->item_type) {
                $itemType = $latestAdjustment->item_type;
            }

            $stockComparison[] = (object)[
                'item_id' => $item->item_id,
                'item_name' => $item->name_item,
                'item_code' => $item->code_item,
                'qty_system' => $qtySystem,
                'qty_physical' => $qtyPhysical,
                'qty_difference' => $qtyDifference,
                'unit' => '-',
                'reason' => $latestAdjustment->reason ?? '-',
                'adjusted_at' => $latestAdjustment->adjusted_at ?? null,
                'adjustment_id' => $latestAdjustment->adjustment_id ?? null,
                'inventory_id' => $itemStock->inventory_id,
                'item_type' => $itemType,
                'rawMaterial' => $item->rawMaterial ?? null
            ];
        }

        // Sort by difference
        usort($stockComparison, function($a, $b) {
            return abs($b->qty_difference) <=> abs($a->qty_difference);
        });

        // Calculate comparison stats
        $comparisonStats = [
            'total_items_checked' => count($stockComparison),
            'items_with_surplus' => count(array_filter($stockComparison, fn($item) => $item->qty_difference > 0)),
            'items_with_deficit' => count(array_filter($stockComparison, fn($item) => $item->qty_difference < 0)),
            'items_matched' => count(array_filter($stockComparison, fn($item) => $item->qty_difference == 0)),
            'total_difference' => array_sum(array_map(fn($item) => $item->qty_difference, $stockComparison)),
            'branch_name' => $gentleLivingBranch->name_branch
        ];

        // Get adjustment data for history
        $adjustments = StockAdjustment::whereIn('inventory_id', $inventories)
            ->with(['rawMaterial', 'adjustedByUser'])
            ->orderBy('adjusted_at', 'desc')
            ->paginate(15);

        // Get materials with adjustments
        $materialsWithAdjustments = StockAdjustment::whereIn('inventory_id', $inventories)
            ->selectRaw('item_id, COUNT(*) as adjustment_count, SUM(qty_difference) as total_adjustment, unit')
            ->groupBy('item_id', 'unit')
            ->with('rawMaterial')
            ->get();

        // Get summary data
        $adjustmentTypes = StockAdjustment::whereIn('inventory_id', $inventories)
            ->selectRaw("adjustment_type, SUM(ABS(qty_difference)) as qty")
            ->groupBy('adjustment_type')
            ->pluck('qty', 'adjustment_type')
            ->toArray();

        $adjustmentReasons = StockAdjustment::whereIn('inventory_id', $inventories)
            ->selectRaw("reason, SUM(ABS(qty_difference)) as qty")
            ->groupBy('reason')
            ->pluck('qty', 'reason')
            ->toArray();

        $summary = [
            'total_adjustments' => StockAdjustment::whereIn('inventory_id', $inventories)->count(),
            'period_days' => (int) $request->get('days', 30),
            'adjustment_types' => $adjustmentTypes,
            'adjustment_reasons' => $adjustmentReasons
        ];

        // Get final stocks data
        $finalStocksQuery = MasterItemStock::whereIn('inventory_id', $inventories)
            ->with(['item', 'inventory'])
            ->orderBy('stock', 'desc')
            ->get();

        // Map final stocks to include received dates
        $finalStocks = $finalStocksQuery->map(function ($stock) {
            $receivedDate = null;
            $orderDate = null;
            
            // Get latest received date and order date based on item type
            if ($stock->item) {
                // Check if item is raw material
                if ($stock->item->is_raw_material ?? false) {
                    $rawMaterialIn = RawMaterialIn::where('item_raw_id', function ($q) use ($stock) {
                        $q->select('item_raw_id')
                            ->from('master_items_raw_material')
                            ->where('item_id', $stock->item_id);
                    })
                    ->orderBy('received_date', 'desc')
                    ->first();
                    
                    if ($rawMaterialIn) {
                        $receivedDate = $rawMaterialIn->received_date;
                        $orderDate = $rawMaterialIn->created_at;
                    }
                } else {
                    // Check finished goods
                    $finishedGoodsIn = FinishedGoodsIn::where('item_id', $stock->item_id)
                        ->orderBy('received_date', 'desc')
                        ->first();
                    
                    if ($finishedGoodsIn) {
                        $receivedDate = $finishedGoodsIn->received_date;
                        $orderDate = $finishedGoodsIn->production_date;
                    }
                }
            }
            
            $stock->received_date = $receivedDate;
            $stock->order_date = $orderDate;
            
            return $stock;
        });


        // Get raw material outflows
        $rawMaterialOuts = RawMaterialOut::where('branch_id', $gentleLivingBranch->branch_id)
            ->with(['rawMaterial', 'issuedByUser', 'productionOrder'])
            ->orderBy('issued_date', 'desc')
            ->paginate(15);

        // Get recent physical inputs from stock adjustment
        $physicalInputs = StockAdjustment::whereIn('inventory_id', $inventories)
            ->where('reason', 'opname_result')
            ->with([
                'rawMaterial' => function ($query) {
                    $query->select('item_raw_id', 'material_name', 'current_stock', 'unit');
                }
            ])
            ->select([
                'adjustment_id',
                'item_id',
                'item_type',
                'qty_system',
                'qty_physical',
                'qty_difference',
                'adjusted_by',
                'adjusted_at',
                'notes'
            ])
            ->orderBy('adjusted_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($adjustment) {
                // Get item name based on item type
                $itemName = 'N/A';
                if ($adjustment->item_type === 'raw_material' && $adjustment->rawMaterial) {
                    $itemName = $adjustment->rawMaterial->material_name;
                } else if ($adjustment->item_type === 'finished_good') {
                    $item = MasterItem::find($adjustment->item_id);
                    $itemName = $item ? $item->name_item : 'N/A';
                }
                
                $adjustment->item_name = $itemName;
                return $adjustment;
            });

        return view('admin_inventory.stock_opname', compact(
            'stockComparison',
            'comparisonStats',
            'adjustments',
            'materialsWithAdjustments',
            'summary',
            'finalStocks',
            'rawMaterialOuts',
            'physicalInputs'
        ));
    }

    /**
     * POST: /admin/inventory/stock-opname/save-physical-stock
     * Save physical stock value
     */
    public function savePhysicalStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_id' => 'required|integer',
                'qty_physical' => 'required|numeric|min:0',
                'qty_system' => 'required|numeric|min:0',
                'inventory_id' => 'required|integer',
                'adjustment_id' => 'nullable|integer'
            ]);

            // Get inventory to get branch_id
            $inventory = MasterInventory::findOrFail($validated['inventory_id']);

            $qtyDifference = $validated['qty_physical'] - $validated['qty_system'];
            $adjustmentType = $qtyDifference > 0 ? 'increase' : ($qtyDifference < 0 ? 'decrease' : 'none');

            // Create or update stock adjustment
            $adjustment = StockAdjustment::updateOrCreate(
                [
                    'adjustment_id' => $validated['adjustment_id']
                ],
                [
                    'item_type' => 'finished_good',
                    'item_id' => $validated['item_id'],
                    'inventory_id' => $validated['inventory_id'],
                    'branch_id' => $inventory->branch_id,
                    'qty_system' => $validated['qty_system'],
                    'qty_physical' => $validated['qty_physical'],
                    'qty_difference' => $qtyDifference,
                    'qty_after_adjustment' => $validated['qty_physical'],
                    'reason' => 'opname_result',
                    'adjustment_type' => $adjustmentType,
                    'adjusted_by' => Auth::id(),
                    'adjusted_at' => now(),
                    'notes' => 'Updated via stock comparison interface'
                ]
            );

            // Update MasterItemStock
            MasterItemStock::updateOrCreate(
                [
                    'item_id' => $validated['item_id'],
                    'inventory_id' => $validated['inventory_id']
                ],
                [
                    'stock' => (int) $validated['qty_physical']
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Stok fisik berhasil disimpan',
                'data' => [
                    'item_id' => $validated['item_id'],
                    'qty_physical' => $validated['qty_physical'],
                    'qty_difference' => $qtyDifference,
                    'adjustment_id' => $adjustment->adjustment_id
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving physical stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST: /inventory/physical-input
     * Store physical input data for items
     */
    public function storePhysicalInput(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_type' => 'required|in:raw_material,finished_good',
                'item_id' => 'required|integer',
                'qty_physical' => 'required|numeric|min:0.01',
                'warehouse_location' => 'nullable|string|max:255',
                'condition' => 'nullable|in:good,damaged,expired,other',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Get default inventory with proper null checks
            $branch = MasterBranch::where('name_branch', 'like', '%Gentle Living%')->first() 
                ?? MasterBranch::first();
            
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data branch tidak ditemukan. Hubungi administrator.'
                ], 422);
            }
            
            $inventory = MasterInventory::where('branch_id', $branch->branch_id)->first()
                ?? MasterInventory::first();
            
            if (!$inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data inventory tidak ditemukan. Hubungi administrator.'
                ], 422);
            }

            // Get current stock for comparison
            $currentStock = 0;
            if ($validated['item_type'] === 'raw_material') {
                $material = MasterItemRawMaterial::find($validated['item_id']);
                if (!$material) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bahan baku dengan ID ' . $validated['item_id'] . ' tidak ditemukan.'
                    ], 422);
                }
                $currentStock = $material->current_stock ?? 0;
            } else {
                $itemStock = MasterItemStock::where('item_id', $validated['item_id'])
                    ->where('inventory_id', $inventory->inventory_id)
                    ->first();
                if (!$itemStock) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Produk jadi dengan ID ' . $validated['item_id'] . ' tidak ditemukan di inventory ini.'
                    ], 422);
                }
                $currentStock = $itemStock->stock ?? 0;
            }

            $qtyDifference = $validated['qty_physical'] - $currentStock;

            // Create stock adjustment record
            $adjustment = StockAdjustment::create([
                'item_type' => $validated['item_type'],
                'item_id' => $validated['item_id'],
                'inventory_id' => $inventory->inventory_id,
                'branch_id' => $branch->branch_id,
                'qty_system' => $currentStock,
                'qty_physical' => $validated['qty_physical'],
                'qty_difference' => $qtyDifference,
                'qty_after_adjustment' => $validated['qty_physical'],
                'reason' => 'opname_result',
                'adjustment_type' => $qtyDifference > 0 ? 'increase' : ($qtyDifference < 0 ? 'decrease' : 'none'),
                'adjusted_by' => Auth::id(),
                'adjusted_at' => now(),
                'notes' => 'Kondisi: ' . ($validated['condition'] ?? 'tidak ditentukan') . '. ' . 
                          'Lokasi: ' . ($validated['warehouse_location'] ?? 'tidak ditentukan') . '. ' .
                          ($validated['notes'] ? 'Catatan: ' . $validated['notes'] : '')
            ]);

            // Update stock in system
            if ($validated['item_type'] === 'raw_material') {
                MasterItemRawMaterial::where('item_raw_id', $validated['item_id'])
                    ->update(['current_stock' => $validated['qty_physical']]);
            } else {
                MasterItemStock::updateOrCreate(
                    [
                        'item_id' => $validated['item_id'],
                        'inventory_id' => $inventory->inventory_id
                    ],
                    [
                        'stock' => $validated['qty_physical']
                    ]
                );
            }

            Log::info('Physical input stored', [
                'user_id' => Auth::id(),
                'item_type' => $validated['item_type'],
                'item_id' => $validated['item_id'],
                'qty_physical' => $validated['qty_physical'],
                'adjustment_id' => $adjustment->adjustment_id,
                'warehouse_location' => $validated['warehouse_location'] ?? null,
                'condition' => $validated['condition'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data fisik barang berhasil disimpan',
                'data' => [
                    'adjustment_id' => $adjustment->adjustment_id,
                    'qty_physical' => $validated['qty_physical'],
                    'qty_difference' => $qtyDifference,
                    'condition' => $validated['condition'] ?? 'tidak ditentukan',
                    'location' => $validated['warehouse_location'] ?? 'tidak ditentukan'
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing physical input: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/api/raw-materials
     * Get raw materials for dropdown selection in physical input form
     */
    public function getRawMaterialsForSelection()
    {
        try {
            $materials = MasterItemRawMaterial::orderBy('material_name')
                ->select('item_raw_id', 'material_name', 'unit', 'current_stock')
                ->get()
                ->map(function ($material) {
                    return [
                        'id' => $material->item_raw_id,
                        'name' => $material->material_name . ' (' . $material->unit . ')',
                        'text' => $material->material_name,
                        'unit' => $material->unit,
                        'current_stock' => $material->current_stock
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $materials
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting raw materials: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data bahan baku'
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/api/finished-goods
     * Get finished goods for dropdown selection in physical input form
     */
    public function getFinishedGoodsForSelection()
    {
        try {
            $goods = MasterItemStock::with('item')
                ->whereHas('item', function ($query) {
                    // Filter untuk produk jadi (bukan bahan baku)
                    // Bisa disesuaikan dengan kategori atau tipe produk jika ada
                })
                ->orderBy('stock', 'desc')
                ->select('item_stock_id', 'item_id', 'stock')
                ->get()
                ->map(function ($good) {
                    $itemName = optional($good->item)->name_item ?? 'Unknown Item';
                    $itemCode = optional($good->item)->code_item ?? '';
                    $displayName = $itemCode ? "{$itemName} ({$itemCode})" : $itemName;

                    return [
                        'id' => $good->item_id,
                        'item_stock_id' => $good->item_stock_id,
                        'name' => $displayName,
                        'text' => $itemName,
                        'code' => $itemCode,
                        'current_stock' => $good->stock
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $goods
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting finished goods: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data produk jadi'
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/api/received-goods
     * Get goods received within a date range for filter
     */
    public function getReceivedGoodsByDateRange(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            if (!$dateFrom || !$dateTo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date range is required'
                ], 400);
            }

            $receivedGoods = [];

            // Get raw materials received in date range
            $rawMaterialsIn = RawMaterialIn::whereBetween('received_date', [$dateFrom, $dateTo])
                ->with('rawMaterial')
                ->orderBy('received_date', 'desc')
                ->get()
                ->map(function ($material) {
                    return [
                        'id' => $material->item_raw_id,
                        'name' => optional($material->rawMaterial)->material_name,
                        'type' => 'raw_material',
                        'received_date' => $material->received_date,
                        'qty_received' => $material->qty_received,
                        'unit' => $material->unit
                    ];
                });

            // Get finished goods received in date range
            $finishedGoodsIn = FinishedGoodsIn::whereBetween('received_date', [$dateFrom, $dateTo])
                ->with('item')
                ->orderBy('received_date', 'desc')
                ->get()
                ->map(function ($good) {
                    return [
                        'id' => $good->item_id,
                        'name' => optional($good->item)->name_item,
                        'type' => 'finished_good',
                        'received_date' => $good->received_date,
                        'qty_received' => $good->qty_received,
                        'unit' => $good->unit
                    ];
                });

            $receivedGoods = $rawMaterialsIn->concat($finishedGoodsIn)->values();

            return response()->json([
                'success' => true,
                'data' => $receivedGoods
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting received goods: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching received goods'
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/production-overview
     * Display production orders and raw material tracking
     */
    public function productionOverview(Request $request)
    {
        $daysBack = (int) $request->get('days', 30);
        $startDate = Carbon::now()->subDays($daysBack);

        // Recent production orders
        $productionOrders = ProductionOrder::where('planned_date', '>=', $startDate)
            ->with('item')
            ->orderBy('planned_date', 'desc')
            ->paginate(15);

        // Raw materials in/out summary
        $rawMaterialInSummary = RawMaterialIn::where('received_date', '>=', $startDate)
            ->selectRaw('item_raw_id, COUNT(*) as receipt_count, SUM(qty_received) as total_received')
            ->groupBy('item_raw_id')
            ->with('rawMaterial')
            ->get();

        $rawMaterialOutSummary = RawMaterialOut::where('issued_date', '>=', $startDate)
            ->selectRaw('item_raw_id, COUNT(*) as usage_count, SUM(qty_issued) as total_used')
            ->groupBy('item_raw_id')
            ->with('rawMaterial')
            ->get();

        // Production status breakdown
        $productionStatus = ProductionOrder::where('planned_date', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count, SUM(qty_planned) as total_qty')
            ->groupBy('status')
            ->pluck('total_qty', 'status');

        // Finished goods in/out - dengan production code
        $finishedGoodsIn = FinishedGoodsIn::where('received_date', '>=', $startDate)
            ->with('item')
            ->orderBy('received_date', 'desc')
            ->limit(100)
            ->get();

        // Group data untuk summary stats
        $finishedGoodsInSummary = FinishedGoodsIn::where('received_date', '>=', $startDate)
            ->selectRaw('item_id, COUNT(*) as batch_count, SUM(qty_received) as total_produced')
            ->groupBy('item_id')
            ->with('item')
            ->get();

        $finishedGoodsOut = FinishedGoodsOut::where('out_date', '>=', $startDate)
            ->selectRaw('item_id, COUNT(*) as transaction_count, SUM(qty_out) as total_sold')
            ->groupBy('item_id')
            ->with('item')
            ->get();

        $summary = [
            'period_days' => $daysBack,
            'total_production_orders' => $productionOrders->total(),
            'production_status' => $productionStatus->toArray(),
            'total_raw_material_in' => $rawMaterialInSummary->sum('total_received'),
            'total_raw_material_out' => $rawMaterialOutSummary->sum('total_used'),
            'total_finished_goods_in' => $finishedGoodsInSummary->sum('total_produced'),
            'total_finished_goods_out' => $finishedGoodsOut->sum('total_sold')
        ];

        return view('admin_inventory.production_overview', compact(
            'productionOrders',
            'rawMaterialInSummary',
            'rawMaterialOutSummary',
            'finishedGoodsIn',
            'finishedGoodsInSummary',
            'finishedGoodsOut',
            'summary',
            'daysBack'
        ));
    }

    /**
     * GET: /admin/inventory/buffer-stock/details/{itemRawId}
     * Get detailed buffer stock calculation for a specific material
     */
    public function bufferStockDetail($itemRawId)
    {
        $service = new BufferStockCalculationService();
        $material = MasterItemRawMaterial::find($itemRawId);

        if (!$material) {
            return response()->json(['error' => 'Material not found'], 404);
        }

        $calculation = $service->calculateBufferStock($itemRawId);
        $adjustmentAnalysis = $service->getStockAdjustmentAnalysis($itemRawId);

        // Get historical usage data for last 30 days
        $usageHistory = RawMaterialOut::where('item_raw_id', $itemRawId)
            ->where('issued_date', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(issued_date) as date, SUM(qty_issued) as daily_usage')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get receipt history for last 30 days
        $receiptHistory = RawMaterialIn::where('item_raw_id', $itemRawId)
            ->where('received_date', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(received_date) as date, SUM(qty_received) as daily_receipt')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'material' => $material,
            'calculation' => $calculation,
            'adjustment_analysis' => $adjustmentAnalysis,
            'usage_history' => $usageHistory,
            'receipt_history' => $receiptHistory
        ]);
    }

    /**
     * PUT: /admin/inventory/buffer-stock/raw-materials/{itemRawId}
     * Update raw material fields from buffer stock table action
     */
    public function updateBufferStockRawMaterial(Request $request, $itemRawId)
    {
        $validated = $request->validate([
            'material_name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'lead_time_days' => 'required|integer|min:0|max:3650',
            'buffer_stock' => 'required|numeric|min:0',
            'supplier_name' => 'nullable|string|max:255',
        ]);

        $bufferStock = (float) $validated['buffer_stock'];
        $currentStock = (float) $validated['current_stock'];

        $stockStatus = $currentStock <= 0
            ? 'critical'
            : ($currentStock < $bufferStock ? 'low' : 'normal');

        $payload = [
            'material_name' => $validated['material_name'],
            'unit' => $validated['unit'] ?? null,
            'purchase_price' => $validated['purchase_price'],
            'current_stock' => $currentStock,
            'lead_time_days' => (int) $validated['lead_time_days'],
            'buffer_stock' => $bufferStock,
            'supplier_name' => $validated['supplier_name'] ?? null,
            'stock_status' => $stockStatus,
            'avg_daily_usage' => 0,
            'reorder_point' => $bufferStock,
        ];

        $material = MasterItemRawMaterial::updateOrCreate(
            ['item_raw_id' => $itemRawId],
            $payload
        );

        return response()->json([
            'success' => true,
            'message' => 'Data bahan baku berhasil diperbarui.',
            'data' => $material
        ]);
    }

    /**
     * DELETE: /admin/inventory/buffer-stock/raw-materials/{itemRawId}
     * Delete raw material with relation safety checks
     */
    public function destroyBufferStockRawMaterial($itemRawId)
    {
        try {
            $material = MasterItemRawMaterial::find($itemRawId);

            if (!$material) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data bahan baku tidak ditemukan di database.'
                ], 404);
            }

            $hasReferences = $material->rawMaterialIn()->exists()
                || $material->rawMaterialOut()->exists()
                || $material->billOfMaterials()->exists()
                || $material->stockAdjustments()->exists();

            if ($hasReferences) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak dapat dihapus karena masih dipakai pada transaksi atau relasi lain.'
                ], 422);
            }

            $material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data bahan baku berhasil dihapus.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error deleting raw material: ' . $e->getMessage(), [
                'itemRawId' => $itemRawId,
                'error' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/production-master-data
     * Get master data for production modal: raw materials, inventories, and BOM products.
     */
    public function bufferStockProductionMasterData()
    {
        $inventoryOptions = MasterInventory::orderBy('inventory_id')
            ->get(['inventory_id', 'name_inventory', 'branch_id']);

        $materials = MasterItemRawMaterial::orderBy('material_name')
            ->get(['item_raw_id', 'material_name', 'unit', 'current_stock']);

        $itemIds = MasterItem::query()
            ->select('item_id')
            ->pluck('item_id')
            ->values();

        $productOptions = collect();

        foreach ($itemIds as $itemId) {
            $item = MasterItem::find($itemId);
            if (!$item) {
                continue;
            }

            $recipe = $this->buildProductionRecipeForItem((int) $itemId, 1);
            if (empty($recipe['materials'])) {
                continue;
            }

            $maxProducible = (int) ($recipe['max_producible'] ?? 0);
            $bomPreview = collect($recipe['materials'])
                ->map(function ($need) {
                    $raw = $need['raw'] ?? null;

                    return [
                        'item_raw_id' => (int) ($raw->item_raw_id ?? 0),
                        'material_name' => (string) ($need['material_name'] ?? ($raw->material_name ?? '-')),
                        'unit' => (string) ($need['unit'] ?? ($raw->unit ?? '-')),
                        'required_per_unit' => round((float) ($need['required_per_unit'] ?? 0), 1),
                        'stock_now' => (float) ($raw->current_stock ?? 0),
                    ];
                })
                ->values()
                ->all();

            $productOptions->push([
                'item_id' => (int) $item->item_id,
                'code_item' => (string) ($item->code_item ?? ''),
                'name_item' => (string) ($item->name_item ?? '-'),
                'max_producible' => max(0, $maxProducible),
                'recipe_source' => (string) ($recipe['source'] ?? 'bom'),
                'bom_preview' => $bomPreview,
            ]);
        }

        return response()->json([
            'success' => true,
            'materials' => $materials,
            'inventories' => $inventoryOptions,
            'products' => $productOptions,
        ]);
    }

    /**
     * GET: /admin/inventory/buffer-stock/production-options/{itemRawId}
     * Get finished goods that can be produced by selected raw material.
     */
    public function bufferStockProductionOptions($itemRawId)
    {
        $material = MasterItemRawMaterial::find($itemRawId);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Bahan baku tidak ditemukan.'
            ], 404);
        }

        $inventoryOptions = MasterInventory::orderBy('inventory_id')
            ->get(['inventory_id', 'name_inventory', 'branch_id']);

        $itemIds = MasterItem::query()
            ->select('item_id')
            ->pluck('item_id')
            ->values();

        $productOptions = collect();

        foreach ($itemIds as $itemId) {
            $item = MasterItem::find($itemId);
            if (!$item) {
                continue;
            }

            $recipe = $this->buildProductionRecipeForItem((int) $itemId, 1);
            if (empty($recipe['materials'])) {
                continue;
            }

            $containsSelectedRaw = collect($recipe['materials'])
                ->contains(function ($need) use ($itemRawId) {
                    return (int) (($need['raw']->item_raw_id ?? 0)) === (int) $itemRawId;
                });

            if (!$containsSelectedRaw) {
                continue;
            }

            $maxProducible = (int) ($recipe['max_producible'] ?? 0);
            $bomPreview = collect($recipe['materials'])
                ->map(function ($need) {
                    $raw = $need['raw'] ?? null;

                    return [
                        'item_raw_id' => (int) ($raw->item_raw_id ?? 0),
                        'material_name' => (string) ($need['material_name'] ?? ($raw->material_name ?? '-')),
                        'unit' => (string) ($need['unit'] ?? ($raw->unit ?? '-')),
                        'required_per_unit' => round((float) ($need['required_per_unit'] ?? 0), 1),
                        'stock_now' => (float) ($raw->current_stock ?? 0),
                    ];
                })
                ->values()
                ->all();

            $productOptions->push([
                'item_id' => (int) $item->item_id,
                'code_item' => (string) ($item->code_item ?? ''),
                'name_item' => (string) ($item->name_item ?? '-'),
                'max_producible' => max(0, $maxProducible),
                'recipe_source' => (string) ($recipe['source'] ?? 'bom'),
                'bom_preview' => $bomPreview,
            ]);
        }

        return response()->json([
            'success' => true,
            'material' => [
                'item_raw_id' => (int) $material->item_raw_id,
                'material_name' => (string) ($material->material_name ?? '-'),
                'unit' => (string) ($material->unit ?? '-'),
                'current_stock' => (int) ($material->current_stock ?? 0),
            ],
            'inventories' => $inventoryOptions,
            'products' => $productOptions,
        ]);
    }

    /**
     * POST: /admin/inventory/buffer-stock/produce
     * Consume raw materials and increase finished goods stock.
     */
    public function produceFromRawMaterial(Request $request)
    {
        try {
            Log::info('Production request received', [
                'item_id' => $request->item_id,
                'inventory_id' => $request->inventory_id,
                'qty_produced' => $request->qty_produced,
            ]);

            $validated = $request->validate([
                'item_id' => 'required|integer|exists:master_items,item_id',
                'inventory_id' => 'required|integer|exists:master_inventories,inventory_id',
                'qty_produced' => 'required|integer|min:1|max:1000000',
                'selected_materials' => 'nullable|array',
                'selected_materials.*.material_id' => 'required|string',
                'selected_materials.*.required_qty' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            $itemId = (int) $validated['item_id'];
            $inventoryId = (int) $validated['inventory_id'];
            $qtyProduced = (int) $validated['qty_produced'];
            $selectedMaterials = $validated['selected_materials'] ?? [];

            $inventory = MasterInventory::find($inventoryId);
            if (!$inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventori tidak ditemukan.'
                ], 404);
            }

            $branchId = (int) $inventory->branch_id;
            $userId = (int) (Auth::id() ?? 0);

            if ($userId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session user tidak valid. Silakan login ulang.'
                ], 401);
            }

            $recipe = $this->buildProductionRecipeForItem($itemId, $qtyProduced);

            Log::info('Recipe built', [
                'source' => $recipe['source'] ?? 'none',
                'materials_count' => count($recipe['materials'] ?? []),
                'max_producible' => $recipe['max_producible'] ?? 0,
            ]);

            if (empty($recipe['materials'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Racikan produk belum tersedia (BOM/CSV), produksi tidak dapat diproses.'
                ], 422);
            }

            $allMaterialNeeds = collect($recipe['materials'])->map(function ($need) {
                $raw = $need['raw'] ?? null;

                return [
                    'bom' => $need['bom'] ?? null,
                    'raw' => $raw,
                    'material_name' => (string) ($need['material_name'] ?? ($raw->material_name ?? '-')),
                    'required_qty' => round((float) ($need['required_qty'] ?? 0), 1),
                    'unit_cost' => (float) ($raw->purchase_price ?? 0),
                    'total_cost' => round((float) ($need['required_qty'] ?? 0) * (float) ($raw->purchase_price ?? 0), 2),
                ];
            })->values()->all();

            // Filter materials to only selected ones if specified
            $materialNeeds = $allMaterialNeeds;
            if (!empty($selectedMaterials)) {
                $selectedMaterialIds = collect($selectedMaterials)->pluck('material_id')->all();
                $materialNeeds = array_filter($allMaterialNeeds, function ($need) use ($selectedMaterialIds, $selectedMaterials) {
                    $rawId = (int) (($need['raw']->item_raw_id ?? null) ?? 0);
                    return in_array((string) $rawId, $selectedMaterialIds) || 
                           in_array($need['material_name'], array_column($selectedMaterials, 'material_name'));
                });
                $materialNeeds = array_values($materialNeeds);
            }

            if (empty($materialNeeds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada material yang dipilih untuk dikurangi dari stok.'
                ], 422);
            }

            $totalMaterialCost = 0;

            foreach ($materialNeeds as $need) {
                $raw = $need['raw'];
                if (!$raw) {
                    Log::warning('Raw material not found in materialNeeds', [
                        'material_name' => $need['material_name'],
                        'required_qty' => $need['required_qty'],
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Ada bahan baku racikan yang tidak ditemukan di database: ' . $need['material_name']
                    ], 422);
                }

                $requiredQty = (float) ($need['required_qty'] ?? 0);
                $stockNow = (float) ($raw->current_stock ?? 0);
                
                Log::info('Checking raw material stock', [
                    'material_name' => $raw->material_name,
                    'required_qty' => $requiredQty,
                    'stock_now' => $stockNow,
                    'sufficient' => $stockNow >= $requiredQty,
                ]);

                if ($stockNow < $requiredQty) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Stok bahan baku "' . ($raw->material_name ?? '-') . '" tidak mencukupi. Dibutuhkan ' . round($requiredQty, 1) . ', tersedia ' . round($stockNow, 1) . '.'
                    ], 422);
                }

                $totalMaterialCost += (float) ($need['total_cost'] ?? 0);
            }

            Log::info('All materials validated, proceeding to transaction', [
                'item_id' => $itemId,
                'qty_produced' => $qtyProduced,
                'total_cost' => $totalMaterialCost,
                'materials_count' => count($materialNeeds),
            ]);

            $unitCostFinished = $qtyProduced > 0 ? ($totalMaterialCost / $qtyProduced) : 0;

            $now = Carbon::now();
            $orderNumber = 'PRD-' . $now->format('YmdHis') . '-' . $itemId;
            $rawOutDoc = 'RMO-' . $now->format('YmdHis');
            $fgInDoc = 'FGI-' . $now->format('YmdHis');
            $batchNumber = 'BATCH-' . $now->format('YmdHis');

            $result = DB::transaction(function () use (
                $itemId,
                $branchId,
                $userId,
                $orderNumber,
                $qtyProduced,
                $totalMaterialCost,
                $unitCostFinished,
                $materialNeeds,
                $rawOutDoc,
                $fgInDoc,
                $batchNumber,
                $inventoryId,
                $validated,
                $now
            ) {
                try {
                    $productionOrder = ProductionOrder::create([
                        'item_id' => $itemId,
                        'branch_id' => $branchId,
                        'created_by' => $userId,
                        'approved_by' => $userId,
                        'order_number' => $orderNumber,
                        'qty_planned' => $qtyProduced,
                        'qty_produced' => $qtyProduced,
                        'unit' => 'unit',
                        'status' => 'completed',
                        'planned_date' => $now->toDateString(),
                        'started_at' => $now,
                        'completed_at' => $now,
                        'total_material_cost' => round($totalMaterialCost, 2),
                        'overhead_cost' => 0,
                        'hpp_per_unit' => round($unitCostFinished, 2),
                        'notes' => $validated['notes'] ?? null,
                    ]);

                    Log::info('Production order created', [
                        'production_order_id' => $productionOrder->production_order_id,
                        'order_number' => $orderNumber,
                    ]);

                    foreach ($materialNeeds as $need) {
                        $raw = $need['raw'];
                        $requiredQty = round((float) $need['required_qty'], 1);
                        $stockBefore = (float) ($raw->current_stock ?? 0);
                        $stockAfter = max(0, round($stockBefore - $requiredQty, 1));

                        Log::info('Updating raw material stock', [
                            'item_raw_id' => $raw->item_raw_id,
                            'material_name' => $raw->material_name,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'decrease_by' => $requiredQty,
                        ]);

                        $raw->update([
                            'current_stock' => $stockAfter,
                        ]);

                        // Verify update
                        $raw->refresh();
                        Log::info('Raw material stock verified', [
                            'item_raw_id' => $raw->item_raw_id,
                            'current_stock' => $raw->current_stock,
                        ]);

                        RawMaterialOut::create([
                            'item_raw_id' => (int) $raw->item_raw_id,
                            'production_order_id' => (int) $productionOrder->production_order_id,
                            'bom_id' => isset($need['bom']) && $need['bom'] ? (int) $need['bom']->bom_id : null,
                            'branch_id' => $branchId,
                            'issued_by' => $userId,
                            'document_number' => $rawOutDoc,
                            'qty_requested' => $requiredQty,
                            'qty_issued' => $requiredQty,
                            'unit' => $raw->unit,
                            'unit_cost' => round((float) $need['unit_cost'], 1),
                            'total_cost' => round((float) $need['total_cost'], 2),
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'reason' => 'production',
                            'issued_date' => $now->toDateString(),
                            'notes' => 'Auto produksi dari dashboard buffer stock.',
                        ]);

                        Log::info('Raw material out created', [
                            'item_raw_id' => $raw->item_raw_id,
                        ]);
                    }

                    $finishedStock = MasterItemStock::where('item_id', $itemId)
                        ->where('inventory_id', $inventoryId)
                        ->first();

                    $stockBeforeFinished = (int) ($finishedStock->stock ?? 0);
                    $stockAfterFinished = $stockBeforeFinished + $qtyProduced;

                    Log::info('Updating finished goods stock', [
                        'item_id' => $itemId,
                        'inventory_id' => $inventoryId,
                        'stock_before' => $stockBeforeFinished,
                        'stock_after' => $stockAfterFinished,
                        'increase_by' => $qtyProduced,
                    ]);

                    if ($finishedStock) {
                        $finishedStock->update([
                            'stock' => $stockAfterFinished,
                        ]);
                        // Verify update
                        $finishedStock->refresh();
                        Log::info('Finished goods stock updated and verified', [
                            'item_id' => $itemId,
                            'stock' => $finishedStock->stock,
                        ]);
                    } else {
                        MasterItemStock::create([
                            'item_id' => $itemId,
                            'inventory_id' => $inventoryId,
                            'stock' => $stockAfterFinished,
                            'buffer_stock' => 0,
                        ]);
                        Log::info('New finished goods stock created', [
                            'item_id' => $itemId,
                            'stock' => $stockAfterFinished,
                        ]);
                    }

                    // Generate production code
                    try {
                        $productionCodeService = new ProductionCodeService();
                        $productionCode = $productionCodeService->generateProductionCode($itemId, $branchId);
                    } catch (\Exception $e) {
                        Log::error('Production Code Generation Error: ' . $e->getMessage(), [
                            'item_id' => $itemId,
                            'branch_id' => $branchId,
                            'exception' => $e
                        ]);
                        throw new \Exception(
                            "Gagal generate kode produksi. " . $e->getMessage()
                        );
                    }

                    FinishedGoodsIn::create([
                        'item_id' => $itemId,
                        'production_order_id' => (int) $productionOrder->production_order_id,
                        'inventory_id' => $inventoryId,
                        'branch_id' => $branchId,
                        'received_by' => $userId,
                        'document_number' => $fgInDoc,
                        'batch_number' => $batchNumber,
                        'production_code' => $productionCode,
                        'qty_received' => $qtyProduced,
                        'unit' => 'unit',
                        'unit_cost' => round($unitCostFinished, 1),
                        'total_cost' => round($totalMaterialCost, 2),
                        'stock_before' => $stockBeforeFinished,
                        'stock_after' => $stockAfterFinished,
                        'production_date' => $now->toDateString(),
                        'received_date' => $now->toDateString(),
                        'qc_status' => 'passed',
                        'qc_notes' => 'Auto pass dari produksi dashboard.',
                        'notes' => 'Auto produksi dari dashboard buffer stock.',
                    ]);

                    Log::info('Finished goods in created', [
                        'item_id' => $itemId,
                        'qty_received' => $qtyProduced,
                        'production_code' => $productionCode,
                    ]);

                    return [
                        'production_order_id' => (int) $productionOrder->production_order_id,
                        'qty_produced' => $qtyProduced,
                        'total_material_cost' => round($totalMaterialCost, 2),
                        'hpp_per_unit' => round($unitCostFinished, 2),
                        'stock_after_finished' => $stockAfterFinished,
                        'materials_consumed' => count($materialNeeds),
                    ];
                } catch (\Exception $e) {
                    Log::error('Error in transaction', [
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }, 5);

            return response()->json([
                'success' => true,
                'message' => 'Produksi berhasil! Diproduksi ' . $qtyProduced . ' unit. ' . count($materialNeeds) . ' bahan baku dikurangi dari stok dan stok produk jadi bertambah ' . $qtyProduced . ' unit.',
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', array_reduce($e->errors(), function($carry, $item) { return array_merge($carry, (array)$item); }, [])),
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Production error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildProductionRecipeForItem(int $itemId, int $qtyProduced): array
    {
        $item = MasterItem::find($itemId);
        if (!$item) {
            return ['source' => 'none', 'materials' => [], 'max_producible' => 0];
        }

        $recipes = $this->parseProductionRecipesFromCsv();
        $matchedRecipe = $this->matchCsvRecipeToItem($item, $recipes);

        if ($matchedRecipe) {
            $rawMaterials = MasterItemRawMaterial::all();
            $materials = [];
            $maxProducible = null;

            foreach ($matchedRecipe['ingredients'] as $ingredient) {
                $raw = $this->matchRawMaterialByName((string) ($ingredient['material_name'] ?? ''), $rawMaterials);
                
                $requiredPerUnit = (float) ($ingredient['required_per_unit'] ?? 0);
                $csvUnit = (string) ($ingredient['unit'] ?? '');
                
                if ($requiredPerUnit <= 0) {
                    continue;
                }

                // Convert CSV unit to database unit before calculation
                if ($raw && $csvUnit !== '') {
                    $dbUnit = (string) ($raw->unit ?? '');
                    $requiredPerUnit = $this->convertUnitTo($requiredPerUnit, $csvUnit, $dbUnit);
                }

                $requiredPerUnit = round($requiredPerUnit, 1);

                if ($raw) {
                    $stockNow = (float) ($raw->current_stock ?? 0);
                    $maxByRaw = (int) floor($stockNow / $requiredPerUnit);
                    $maxProducible = is_null($maxProducible) ? $maxByRaw : min($maxProducible, $maxByRaw);
                } else {
                    $maxProducible = 0;
                }

                $materials[] = [
                    'bom' => null,
                    'raw' => $raw,
                    'material_name' => (string) ($raw->material_name ?? ($ingredient['material_name'] ?? '-')),
                    'unit' => (string) ($raw->unit ?? 'unit'),
                    'required_per_unit' => $requiredPerUnit,
                    'required_qty' => round($requiredPerUnit * $qtyProduced, 1),
                ];
            }

            // Deduplicate materials by item_raw_id to prevent duplicate entries
            $seenRawIds = [];
            $materials = array_filter($materials, function ($mat) use (&$seenRawIds) {
                $rawId = $mat['raw'] ? (int) $mat['raw']->item_raw_id : null;
                if (!$rawId || in_array($rawId, $seenRawIds)) {
                    return false; // Skip duplicates
                }
                $seenRawIds[] = $rawId;
                return true;
            });
            $materials = array_values($materials); // Re-index array

            if (!empty($materials)) {
                return [
                    'source' => 'csv',
                    'materials' => $materials,
                    'max_producible' => max(0, (int) ($maxProducible ?? 0)),
                ];
            }
        }

        $bomRows = MasterItemBillOfMaterials::with('rawMaterial')
            ->where('item_id', $itemId)
            ->get();

        if ($bomRows->isEmpty()) {
            return ['source' => 'none', 'materials' => [], 'max_producible' => 0];
        }

        $materials = [];
        $maxProducible = null;

        foreach ($bomRows as $bom) {
            $raw = $bom->rawMaterial;
            if (!$raw) {
                continue;
            }

            $requiredPerUnit = (float) $bom->quantity_required;
            $yield = (float) ($bom->yield_percentage ?? 0);

            if ($yield > 0 && $yield < 100) {
                $requiredPerUnit = $requiredPerUnit / ($yield / 100);
            }

            $requiredPerUnit = round($requiredPerUnit, 1);
            if ($requiredPerUnit <= 0) {
                continue;
            }

            $stockNow = (float) ($raw->current_stock ?? 0);
            $maxByRaw = (int) floor($stockNow / $requiredPerUnit);
            $maxProducible = is_null($maxProducible) ? $maxByRaw : min($maxProducible, $maxByRaw);

            $materials[] = [
                'bom' => $bom,
                'raw' => $raw,
                'material_name' => (string) ($raw->material_name ?? '-'),
                'unit' => (string) ($raw->unit ?? '-'),
                'required_per_unit' => $requiredPerUnit,
                'required_qty' => round($requiredPerUnit * $qtyProduced, 1),
            ];
        }

        return [
            'source' => 'bom',
            'materials' => $materials,
            'max_producible' => max(0, (int) ($maxProducible ?? 0)),
        ];
    }

    private function parseProductionRecipesFromCsv(): array
    {
        $path = base_path('python/Kalkulator_Produksi_Sesuai_Excel_update.csv');
        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $recipes = [];
        $currentProduct = null;
        $inIngredientSection = false;

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            $col0 = trim((string) ($row[0] ?? ''));
            $col1 = trim((string) ($row[1] ?? ''));

            if ($col0 === '' && $col1 === '') {
                continue;
            }

            if (stripos($col0, 'Produk:') === 0) {
                $productName = trim(substr($col0, strlen('Produk:')));
                $currentProduct = $productName;
                $recipes[$currentProduct] = [
                    'product_name' => $productName,
                    'ingredients' => [],
                ];
                $inIngredientSection = false;
                continue;
            }

            if (!$currentProduct) {
                continue;
            }

            if (strcasecmp($col0, 'Bahan') === 0) {
                $inIngredientSection = true;
                continue;
            }

            if (!$inIngredientSection) {
                continue;
            }

            if ($col0 === '' || stripos($col0, 'Potensi Hasil Produk') === 0) {
                continue;
            }

            $parsedData = $this->parseValueWithUnit($col1);
            if (!$parsedData) {
                continue;
            }

            $requiredPerUnit = $parsedData['value'];
            $unit = $parsedData['unit'];

            $recipes[$currentProduct]['ingredients'][] = [
                'material_name' => $col0,
                'required_per_unit' => $requiredPerUnit,
                'unit' => $unit,
            ];
        }

        return array_values(array_filter($recipes, function ($recipe) {
            return !empty($recipe['ingredients']);
        }));
    }

    private function matchCsvRecipeToItem(MasterItem $item, array $recipes): ?array
    {
        $itemName = strtoupper((string) ($item->name_item ?? ''));
        $itemCode = strtoupper((string) ($item->code_item ?? ''));
        $itemVolume = $this->extractMlValue($itemName . ' ' . $itemCode);

        foreach ($recipes as $recipe) {
            $productName = strtoupper((string) ($recipe['product_name'] ?? ''));
            [$recipeCode, $recipeVolume] = $this->extractRecipeProductCodeAndVolume($productName);

            if ($recipeCode === '') {
                continue;
            }

            $codeMatched = str_contains($itemName, $recipeCode)
                || str_contains($itemCode, $recipeCode);

            if (!$codeMatched) {
                continue;
            }

            if ($recipeVolume !== null && $itemVolume !== null) {
                if (abs($recipeVolume - $itemVolume) > 0.01) {
                    continue;
                }
            }

            return $recipe;
        }

        return null;
    }

    private function matchRawMaterialByName(string $csvMaterialName, $rawMaterials): ?MasterItemRawMaterial
    {
        $needle = $this->normalizeText($csvMaterialName);
        if ($needle === '') {
            return null;
        }

        foreach ($rawMaterials as $raw) {
            $name = $this->normalizeText((string) ($raw->material_name ?? ''));
            if ($name === $needle) {
                return $raw;
            }
        }

        foreach ($rawMaterials as $raw) {
            $name = $this->normalizeText((string) ($raw->material_name ?? ''));
            if (str_contains($name, $needle) || str_contains($needle, $name)) {
                return $raw;
            }
        }

        return null;
    }

    /**
     * Parse value and unit from text
     * e.g., "2 liter" → ['value' => 2.0, 'unit' => 'liter']
     * e.g., "0.15 ml" → ['value' => 0.15, 'unit' => 'ml']
     * e.g., "5" → ['value' => 5.0, 'unit' => '']
     */
    private function parseValueWithUnit(string $text): ?array
    {
        if ($text === '') {
            return null;
        }

        // Match: number (optional decimals) + optional space + optional unit
        if (!preg_match('/(\d+(?:[\.,]\d+)?)\s*([a-zA-Z]*)/i', $text, $matches)) {
            return null;
        }

        $value = (float) str_replace(',', '.', $matches[1]);
        if ($value <= 0) {
            return null;
        }

        $unit = strtolower(trim($matches[2] ?? ''));

        return [
            'value' => $value,
            'unit' => $unit,
        ];
    }

    /**
     * Convert value from one unit to another
     * Supports: volume (liter/l/ml) and weight (kg/g)
     * Returns converted value, or original value if units are incompatible
     */
    private function convertUnitTo(float $value, string $fromUnit, string $toUnit): float
    {
        $from = strtolower(trim($fromUnit));
        $to = strtolower(trim($toUnit));

        // If units are the same, no conversion
        if ($from === $to) {
            return $value;
        }

        // Volume conversions (normalize to ml)
        $volumeUnits = ['liter', 'l', 'litre', 'ml', 'milliliter', 'millilitre', 'cc'];
        $isFromVolume = in_array($from, $volumeUnits);
        $isToVolume = in_array($to, $volumeUnits);

        if ($isFromVolume && $isToVolume) {
            // Convert from unit to ml first
            $valueInMl = $value;
            if (in_array($from, ['liter', 'l', 'litre'])) {
                $valueInMl = $value * 1000;
            }

            // Convert from ml to target unit
            if (in_array($to, ['liter', 'l', 'litre'])) {
                return $valueInMl / 1000;
            }
            return $valueInMl; // Return as ml
        }

        // Weight conversions (normalize to gram)
        $weightUnits = ['kg', 'kilogram', 'g', 'gram'];
        $isFromWeight = in_array($from, $weightUnits);
        $isToWeight = in_array($to, $weightUnits);

        if ($isFromWeight && $isToWeight) {
            // Convert from unit to gram first
            $valueInGram = $value;
            if (in_array($from, ['kg', 'kilogram'])) {
                $valueInGram = $value * 1000;
            }

            // Convert from gram to target unit
            if (in_array($to, ['kg', 'kilogram'])) {
                return $valueInGram / 1000;
            }
            return $valueInGram; // Return as gram
        }

        // If units are incompatible (e.g., liter to kg), return original value
        // This case would indicate a data error, but we're lenient
        return $value;
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower($text);
        return preg_replace('/[^a-z0-9]/', '', $text) ?? '';
    }

    private function parseDecimalValue(string $text): ?float
    {
        if ($text === '') {
            return null;
        }

        if (!preg_match('/\d+(?:[\.,]\d+)?/', $text, $matches)) {
            return null;
        }

        $value = str_replace(',', '.', $matches[0]);
        return is_numeric($value) ? (float) $value : null;
    }

    private function extractMlValue(string $text): ?float
    {
        if (!preg_match('/(\d+(?:[\.,]\d+)?)\s*ml/i', $text, $matches)) {
            return null;
        }

        $value = str_replace(',', '.', $matches[1]);
        return is_numeric($value) ? (float) $value : null;
    }

    private function extractRecipeProductCodeAndVolume(string $productName): array
    {
        if (preg_match('/([A-Z0-9]+)\s*-\s*(\d+(?:[\.,]\d+)?)\s*ML/i', $productName, $matches)) {
            $code = strtoupper(trim($matches[1]));
            $volume = (float) str_replace(',', '.', $matches[2]);
            return [$code, $volume];
        }

        if (preg_match('/([A-Z0-9]+)/i', $productName, $matches)) {
            return [strtoupper(trim($matches[1])), null];
        }

        return ['', null];
    }

    /**
     * POST: /admin/inventory/buffer-stock/sync
     * Sync all buffer stocks calculations to database
     */
    public function syncBufferStocks()
    {
        try {
            $service = new BufferStockCalculationService();
            $result = $service->syncAllBufferStocks();

            // Check if all items failed
            if ($result['updated'] === 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal! Semua {$result['total_materials']} bahan gagal diperbarui. Silakan periksa logs untuk detail error.",
                    'data' => $result
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil! {$result['updated']} dari {$result['total_materials']} bahan diperbarui.",
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("Buffer stock sync error: " . $e->getMessage(), $e->getTrace());
            
            return response()->json([
                'success' => false,
                'message' => "Error sinkronisasi: " . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/items-to-order
     * Get list of items that need to be ordered
     */
    public function itemsToOrder()
    {
        $service = new BufferStockCalculationService();

        $materials = MasterItemRawMaterial::where('stock_status', 'critical')
            ->orWhere(function ($q) {
                $q->where('stock_status', 'low')->where('current_stock', '<', 100);
            })
            ->get();

        $itemsNeedingOrder = $materials->map(function ($material) use ($service) {
            $calculation = $service->calculateBufferStock($material->item_raw_id);
            $shortageQty = $calculation['reorder_point'] - $material->current_stock;

            return array_merge($calculation, [
                'shortage_quantity' => max(0, $shortageQty),
                'estimated_cost' => max(0, $shortageQty) * $material->purchase_price,
                'min_order_qty' => max($calculation['min_reorder_qty'], round($shortageQty, 0))
            ]);
        });

        $summary = [
            'total_items_low' => $itemsNeedingOrder->count(),
            'total_shortage_qty' => $itemsNeedingOrder->sum('shortage_quantity'),
            'total_estimated_cost' => $itemsNeedingOrder->sum('estimated_cost')
        ];

        return response()->json([
            'success' => true,
            'items' => $itemsNeedingOrder,
            'summary' => $summary
        ]);
    }

    /**
     * POST: /admin/inventory/buffer-stock/sync-from-csv
     * Sync buffer stock calculations from CSV to database
     */
    public function syncBufferStockFromCSV(Request $request)
    {
        $analysisService = new InventoryAnalysisService();

        try {
            $csvPath = storage_path('app/python/master_items_raw_material.csv');
            if (!file_exists($csvPath)) {
                $csvPath = base_path('python/master_items_raw_material.csv');
            }
            if (!file_exists($csvPath)) {
                $csvPath = public_path('master_items_raw_material.csv');
            }

            // Import and process CSV data
            $csvData = $analysisService->importFromCSV($csvPath);
            $processedData = $analysisService->processAllItems($csvData);

            // Sync to database (using updateOrCreate, so all should succeed)
            $result = $analysisService->syncToDatabase($processedData);

            // Log sync details
            Log::info('Buffer Stock CSV Sync', [
                'synced' => $result['synced'],
                'failed' => $result['failed'],
                'total' => $result['total'],
                'timestamp' => now()
            ]);

            // Check if all succeeded
            if ($result['failed'] === 0) {
                return response()->json([
                    'success' => true,
                    'message' => "✅ Sinkronisasi berhasil! Semua {$result['total']} bahan telah diperbarui.",
                    'data' => $result,
                    'redirect_url' => route('admin.inventory.buffer-stock.raw-materials')
                ]);
            } else {
                // Some items failed - log but still return success with warning
                $failedItems = array_filter($result['details'], fn($d) => $d['status'] !== 'success');
                
                Log::warning('Some items failed during sync', [
                    'synced' => $result['synced'],
                    'failed' => $result['failed'],
                    'failed_items' => array_slice($failedItems, 0, 5)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "⚠️ Sinkronisasi selesai dengan peringatan: {$result['synced']} berhasil, {$result['failed']} gagal dari {$result['total']} bahan. Periksa logs untuk detail.",
                    'data' => $result,
                    'redirect_url' => route('admin.inventory.buffer-stock.raw-materials')
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Buffer Stock CSV Sync Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sinkronisasi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/export-analysis
     * Export inventory analysis to Excel
     */
    public function exportInventoryAnalysis()
    {
        $analysisService = new InventoryAnalysisService();

        try {
            $csvPath = storage_path('app/python/master_items_raw_material.csv');
            if (!file_exists($csvPath)) {
                $csvPath = base_path('python/master_items_raw_material.csv');
            }
            if (!file_exists($csvPath)) {
                $csvPath = public_path('master_items_raw_material.csv');
            }

            // Import and process CSV data
            $csvData = $analysisService->importFromCSV($csvPath);
            $processedData = $analysisService->processAllItems($csvData);
            $exportData = $analysisService->exportAnalysis($processedData);

            // Create Excel file
            $filename = 'inventory_analysis_' . now()->format('Ymd_His') . '.csv';
            
            $file = fopen('php://memory', 'w');
            fputcsv($file, array_keys($exportData[0]));
            
            foreach ($exportData as $row) {
                fputcsv($file, $row);
            }
            
            rewind($file);
            $content = stream_get_contents($file);
            fclose($file);

            return response($content, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\""
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Error exporting: ' . $e->getMessage());
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/import-status
     * Show import status from CSV
     */
    public function bufferStockImportStatus()
    {
        $analysisService = new InventoryAnalysisService();

        try {
            $csvPath = storage_path('app/python/master_items_raw_material.csv');
            if (!file_exists($csvPath)) {
                $csvPath = base_path('python/master_items_raw_material.csv');
            }
            if (!file_exists($csvPath)) {
                $csvPath = public_path('master_items_raw_material.csv');
            }

            // Import and process CSV data
            $csvData = $analysisService->importFromCSV($csvPath);
            $processedData = $analysisService->processAllItems($csvData);
            $summary = $analysisService->generateSummary($processedData);
            $criticalItems = $analysisService->getCriticalItems($processedData);

            return view('admin_inventory.buffer_stock_import_status', compact(
                'summary',
                'criticalItems',
                'csvPath'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Helper function to determine stock status
     */
    private function determineStockStatus($currentStock, $bufferStock)
    {
        if ($currentStock <= 0) {
            return 'critical';
        } elseif ($currentStock < $bufferStock) {
            return 'low';
        } else {
            return 'normal';
        }
    }

    /**
     * POST: Update Buffer Stock dari CSV ROP values
     * Menjalankan update ROP ke database tanpa script Python
     */
    public function updateBufferStockFromRop(Request $request)
    {
        try {
            // Check user authorization
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda harus login terlebih dahulu'
                ], 401);
            }

            // Check if user has required role or permission
            $hasAccess = false;
            if (method_exists($user, 'hasRole')) {
                $hasAccess = $user->hasRole(['owner', 'admin_inventory']);
            }
            if (!$hasAccess && method_exists($user, 'hasPermissionTo')) {
                $hasAccess = $user->hasPermissionTo('update-finished-goods');
            }

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk update buffer stock'
                ], 403);
            }

            $inventoryId = (int)$request->get('inventory_id', 1);

            // Step 1: Try using Python service (Solusi 2) untuk hasil lebih akurat
            Log::info("Attempting to update buffer stock via Python service");
            
            $pythonService = new \App\Services\PythonBufferStockService();
            $result = $pythonService->executeUpdate($inventoryId);

            // Step 2: Fallback ke ROPBufferStockUpdaterService jika Python tidak tersedia
            if (!$result['success'] && (
                strpos($result['message'], 'Python') !== false || 
                strpos($result['message'], 'tidak ditemukan') !== false ||
                strpos($result['message'], 'script') !== false
            )) {
                Log::warning("Python service failed, falling back to ROP CSV service: " . $result['message']);
                
                $ropService = new \App\Services\ROPBufferStockUpdaterService(
                    'buffer_stock_per_produk.csv',
                    'product_mapping.json'
                );
                $result = $ropService->updateBufferStock($inventoryId);
            }

            // Sanitize result untuk ensure format yang benar
            $result = $this->sanitizeBufferStockResult($result);

            if ($result['success']) {
                Log::info('Buffer stock updated successfully');
                Log::info('Updated: ' . $result['updated'] . ', Skipped: ' . $result['skipped'] . ', Errors: ' . $result['errors']);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'updated' => $result['updated'],
                        'skipped' => $result['skipped'],
                        'errors' => $result['errors']
                    ]
                ]);
            } else {
                Log::warning('Buffer stock update incomplete: ' . $result['message']);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => [
                        'updated' => $result['updated'],
                        'skipped' => $result['skipped'],
                        'errors' => $result['errors']
                    ]
                ], 400);
            }

        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            
            Log::error('Error updating buffer stock: ' . $errorMsg);
            Log::debug('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $errorMsg
            ], 500);
        }
    }

    /**
     * Sanitize result dari buffer stock update services
     * Memastikan semua values adalah scalar (bukan array/object)
     */
    private function sanitizeBufferStockResult(array $result): array
    {
        return [
            'success' => (bool)($result['success'] ?? false),
            'message' => (string)($result['message'] ?? 'Unknown error'),
            'updated' => (int)($result['updated'] ?? $result['updated_count'] ?? 0),
            'skipped' => (int)($result['skipped'] ?? $result['not_found_count'] ?? 0),
            'errors' => (int)($result['errors'] ?? $result['error_count'] ?? 0),
        ];
    }

    /**
     * GET: /admin/inventory/buffer-stock/export
     * Export buffer stock raw materials ke file Excel
     */
    public function exportBufferStockExcel()
    {
        try {
            $service = new \App\Services\BufferStockExcelService();
            $filePath = $service->exportToExcel();

            return response()->download($filePath, 'buffer_stock_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error exporting buffer stock: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengekspor file: ' . $e->getMessage());
        }
    }

    /**
     * GET: /admin/inventory/buffer-stock/download-template
     * Download template Excel untuk import buffer stock
     */
    public function downloadBufferStockTemplate()
    {
        try {
            $service = new \App\Services\BufferStockExcelService();
            $filePath = $service->createImportTemplate();

            return response()->download($filePath, 'buffer_stock_template.xlsx')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error downloading template: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengunduh template: ' . $e->getMessage());
        }
    }

    /**
     * POST: /admin/inventory/buffer-stock/import
     * Import buffer stock raw materials dari file Excel
     */
    public function importBufferStockExcel(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:5120' // Max 5MB
            ]);

            $file = $request->file('file');
            $filePath = $file->store('imports', 'local');
            $fullPath = storage_path('app/' . $filePath);

            $service = new \App\Services\BufferStockExcelService();
            $result = $service->importFromExcel($fullPath);

            // Clean up uploaded file
            @unlink($fullPath);

            // Return JSON response instead of redirect
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error importing buffer stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimpor file: ' . $e->getMessage()
            ], 500);
        }
    }
}

