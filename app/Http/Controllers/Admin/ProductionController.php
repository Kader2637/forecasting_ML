<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MasterItem;
use App\Models\MasterItemBillOfMaterials;
use App\Models\MasterItemRawMaterial;
use App\Models\ProductionOrder;
use App\Models\FinishedGoodsIn;
use App\Models\MasterItemStock;
use App\Models\MasterInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionController extends Controller
{
    public function index()
    {
        $productionOrders = ProductionOrder::with('item')->orderBy('created_at', 'desc')->paginate(10);
        return view('admin_inventory.production.index', compact('productionOrders'));
    }

    public function create(Request $request)
    {
        // Default target from forecasting if passed via query parameter
        $targetItemId = $request->get('item_id');
        $targetQty = $request->get('qty');

        $finishedGoods = MasterItem::where('status_item', 'active')
            ->orderBy('name_item', 'asc')
            ->get();
            
        $inventories = MasterInventory::all();

        return view('admin_inventory.production.create', compact('finishedGoods', 'targetItemId', 'targetQty', 'inventories'));
    }

    public function calculateBom(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'qty' => 'required|numeric|min:1'
        ]);

        $itemId = $request->item_id;
        $qty = $request->qty;

        $bomRules = MasterItemBillOfMaterials::with('rawMaterial')
            ->where('item_id', $itemId)
            ->get();

        if ($bomRules->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Bill of Materials tidak ditemukan untuk produk ini.'
            ]);
        }

        $materials = [];
        $canProduce = true;

        foreach ($bomRules as $rule) {
            if (!$rule->rawMaterial) continue;

            $reqPerUnit = (float) $rule->quantity_required;
            $yield = (float) ($rule->yield_percentage ?? 100.00);
            $reqPerUnitAdjusted = $reqPerUnit / ($yield / 100.00);
            $totalRequired = ceil($reqPerUnitAdjusted * $qty * 100) / 100;
            
            $currentStock = (float) $rule->rawMaterial->current_stock;
            $status = $currentStock >= $totalRequired;
            if (!$status) {
                $canProduce = false;
            }

            $materials[] = [
                'raw_material_id' => $rule->rawMaterial->item_raw_id,
                'name' => $rule->rawMaterial->material_name,
                'unit' => $rule->rawMaterial->unit,
                'required' => $totalRequired,
                'available' => $currentStock,
                'enough' => $status
            ];
        }

        return response()->json([
            'success' => true,
            'can_produce' => $canProduce,
            'materials' => $materials
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer|exists:master_items,item_id',
            'inventory_id' => 'required|integer|exists:master_inventories,inventory_id',
            'qty' => 'required|numeric|min:1',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $itemId = $request->item_id;
            $qty = $request->qty;
            $inventoryId = $request->inventory_id;
            
            // Generate Production Code
            $datePrefix = date('Ymd');
            $lastOrder = ProductionOrder::whereDate('created_at', date('Y-m-d'))->count();
            $productionCode = 'PO-' . $datePrefix . '-' . str_pad($lastOrder + 1, 4, '0', STR_PAD_LEFT);

            $bomRules = MasterItemBillOfMaterials::with('rawMaterial')->where('item_id', $itemId)->get();

            if ($bomRules->isEmpty()) {
                throw new \Exception("Bill of Materials tidak ditemukan.");
            }

            // Deduct Raw Materials
            $totalCost = 0;
            foreach ($bomRules as $rule) {
                $reqPerUnit = (float) $rule->quantity_required;
                $yield = (float) ($rule->yield_percentage ?? 100.00);
                $reqPerUnitAdjusted = $reqPerUnit / ($yield / 100.00);
                $totalRequired = ceil($reqPerUnitAdjusted * $qty * 100) / 100;
                
                $rawMaterial = $rule->rawMaterial;
                
                if ($rawMaterial->current_stock < $totalRequired) {
                    throw new \Exception("Stok bahan baku {$rawMaterial->material_name} tidak mencukupi.");
                }

                $rawMaterial->current_stock -= $totalRequired;
                $rawMaterial->save();
                
                $totalCost += ($totalRequired * $rawMaterial->purchase_price);
            }

            // Calculate Unit Cost
            $unitCost = $totalCost / $qty;

            // Create Production Order
            $productionOrder = ProductionOrder::create([
                'order_number' => $productionCode,
                'branch_id' => 1,
                'created_by' => 1,
                'item_id' => $itemId,
                'status' => 'completed',
                'qty_planned' => $qty,
                'qty_produced' => $qty,
                'planned_date' => now()->toDateString(),
                'started_at' => now(),
                'completed_at' => now(),
                'total_material_cost' => $totalCost,
                'hpp_per_unit' => $unitCost,
                'notes' => $request->notes
            ]);

            // Add Finished Goods In
            $fgIn = FinishedGoodsIn::create([
                'item_id' => $itemId,
                'production_order_id' => $productionOrder->production_order_id,
                'inventory_id' => $inventoryId,
                'branch_id' => 1, // Default branch
                'received_by' => 1,
                'document_number' => $productionCode,
                'qty_received' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'production_date' => now(),
                'received_date' => now(),
                'notes' => 'Dari produksi ' . $productionCode
            ]);

            // Update MasterItemStock
            $itemStock = MasterItemStock::firstOrCreate(
                ['item_id' => $itemId, 'inventory_id' => $inventoryId],
                ['stock' => 0, 'buffer_stock' => 0]
            );
            
            $itemStock->stock += $qty;
            $itemStock->save();

            DB::commit();

            return redirect()->route('admin.production.index')->with('success', 'Produksi berhasil diselesaikan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Production Error: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
