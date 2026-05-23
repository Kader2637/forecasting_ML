<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockAdjustment;
use App\Models\MasterItemStock;
use App\Models\MasterItemRawMaterial;
use App\Models\MasterInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StockAdjustment::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reason', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%")
                  ->orWhere('item_type', 'LIKE', "%{$search}%");
            });
        }

        $adjustments = $query->paginate(10);
        return view('admin_inventory.stock_adjustment.index', compact('adjustments'));
    }

    public function create()
    {
        $finishedGoods = MasterItemStock::with(['item', 'inventory'])->get();
        $rawMaterials = MasterItemRawMaterial::get();
        $inventories = MasterInventory::get();

        return view('admin_inventory.stock_adjustment.create', compact('finishedGoods', 'rawMaterials', 'inventories'));
    }

    public function getSystemStock(Request $request)
    {
        $type = $request->type; // 'fg' or 'rm'
        $id = $request->id;

        if ($type == 'fg') {
            $stock = MasterItemStock::find($id);
            if ($stock) {
                return response()->json(['success' => true, 'system_stock' => $stock->stock]);
            }
        } elseif ($type == 'rm') {
            $rm = MasterItemRawMaterial::find($id);
            if ($rm) {
                return response()->json(['success' => true, 'system_stock' => $rm->current_stock]);
            }
        }

        return response()->json(['success' => false, 'message' => 'Data tidak ditemukan']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_type' => 'required|in:finished_good,raw_material',
            'item_stock_id' => 'required_if:item_type,finished_good|nullable|integer',
            'item_raw_id' => 'required_if:item_type,raw_material|nullable|integer',
            'inventory_id' => 'required|integer',
            'qty_physical' => 'required|numeric|min:0',
            'reason' => 'required|in:transaksi,cacat,retur,lainnya',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $qtyPhysical = $request->qty_physical;
            $reason = $request->reason;
            $itemType = $request->item_type;
            
            $docNumber = 'SA-' . date('Ymd') . '-' . rand(1000, 9999);

            $qtySystem = 0;
            $itemId = null;
            
            if ($itemType == 'finished_good') {
                $itemStock = MasterItemStock::find($request->item_stock_id);
                if (!$itemStock) throw new \Exception("Produk jadi tidak ditemukan.");
                
                $qtySystem = $itemStock->stock;
                $itemId = $itemStock->item_id;
                
                $qtyDifference = $qtyPhysical - $qtySystem;
                
                // Update system stock
                $itemStock->stock = $qtyPhysical;
                $itemStock->save();
                
            } else {
                $rawMaterial = MasterItemRawMaterial::find($request->item_raw_id);
                if (!$rawMaterial) throw new \Exception("Bahan baku tidak ditemukan.");
                
                $qtySystem = $rawMaterial->current_stock;
                $itemId = $rawMaterial->item_raw_id;
                
                $qtyDifference = $qtyPhysical - $qtySystem;
                
                // Update system stock
                $rawMaterial->current_stock = $qtyPhysical;
                $rawMaterial->save();
            }

            // Map reason to DB enum: 'opname_result', 'damaged', 'expired', 'missing', 'system_error', 'manual', 'other'
            $dbReason = match ($reason) {
                'cacat' => 'damaged',
                'retur' => 'other',
                'transaksi' => 'manual',
                default => 'other',
            };

            // Map adjustment_type to DB enum: 'increase', 'decrease'
            $adjustmentType = $qtyDifference >= 0 ? 'increase' : 'decrease';

            // Create Log
            StockAdjustment::create([
                'item_type' => $itemType == 'finished_good' ? 'finished_good' : 'raw_material',
                'item_id' => $itemId,
                'inventory_id' => $request->inventory_id,
                'branch_id' => 1,
                'document_number' => $docNumber,
                'qty_system' => $qtySystem,
                'qty_physical' => $qtyPhysical,
                'qty_difference' => $qtyDifference,
                'qty_after_adjustment' => $qtyPhysical,
                'unit' => 'unit',
                'reason' => $dbReason,
                'adjustment_type' => $adjustmentType,
                'adjusted_by' => Auth::id() ?? 1,
                'adjusted_at' => now(),
                'notes' => $request->notes
            ]);

            DB::commit();

            return redirect()->route('admin.stock-adjustment.index')->with('success', 'Penyesuaian stok berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock Adjustment Error: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
