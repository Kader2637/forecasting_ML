<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MasterItemRawMaterial;
use Illuminate\Support\Facades\DB;

class InventoryRawMaterialController extends Controller
{
    public function create()
    {
        return view('admin_inventory.master_data.raw_materials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'avg_daily_usage' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'supplier_name' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $rawMaterial = new MasterItemRawMaterial();
            $rawMaterial->material_name = $validated['material_name'];
            $rawMaterial->unit = $validated['unit'];
            $rawMaterial->purchase_price = $validated['purchase_price'] ?? 0;
            $rawMaterial->current_stock = $validated['current_stock'] ?? 0;
            $rawMaterial->avg_daily_usage = $validated['avg_daily_usage'] ?? 0;
            $rawMaterial->lead_time_days = $validated['lead_time_days'] ?? 0;
            $rawMaterial->supplier_name = $validated['supplier_name'] ?? null;
            
            // Auto calculate buffer stock and reorder point if usage and lead time are provided
            if ($rawMaterial->avg_daily_usage > 0 && $rawMaterial->lead_time_days > 0) {
                $rawMaterial->buffer_stock = ($rawMaterial->avg_daily_usage * $rawMaterial->lead_time_days) * 0.2;
                $rawMaterial->reorder_point = ($rawMaterial->avg_daily_usage * $rawMaterial->lead_time_days) + $rawMaterial->buffer_stock;
            }

            // Set initial stock status
            if ($rawMaterial->current_stock <= 0) {
                $rawMaterial->stock_status = 'out_of_stock';
            } elseif ($rawMaterial->current_stock <= $rawMaterial->reorder_point) {
                $rawMaterial->stock_status = 'low';
            } else {
                $rawMaterial->stock_status = 'normal';
            }

            $rawMaterial->save();
            DB::commit();

            return redirect()->route('admin.inventory.buffer-stock.raw-materials')
                ->with('success', 'Data bahan baku berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $rawMaterial = MasterItemRawMaterial::findOrFail($id);
        return view('admin_inventory.master_data.raw_materials.edit', compact('rawMaterial'));
    }

    public function update(Request $request, $id)
    {
        $rawMaterial = MasterItemRawMaterial::findOrFail($id);
        
        $validated = $request->validate([
            'material_name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'avg_daily_usage' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'supplier_name' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $rawMaterial->material_name = $validated['material_name'];
            $rawMaterial->unit = $validated['unit'];
            $rawMaterial->purchase_price = $validated['purchase_price'] ?? 0;
            $rawMaterial->current_stock = $validated['current_stock'] ?? 0;
            $rawMaterial->avg_daily_usage = $validated['avg_daily_usage'] ?? 0;
            $rawMaterial->lead_time_days = $validated['lead_time_days'] ?? 0;
            $rawMaterial->supplier_name = $validated['supplier_name'] ?? null;
            
            // Auto calculate buffer stock and reorder point if usage and lead time are provided
            if ($rawMaterial->avg_daily_usage > 0 && $rawMaterial->lead_time_days > 0) {
                $rawMaterial->buffer_stock = ($rawMaterial->avg_daily_usage * $rawMaterial->lead_time_days) * 0.2;
                $rawMaterial->reorder_point = ($rawMaterial->avg_daily_usage * $rawMaterial->lead_time_days) + $rawMaterial->buffer_stock;
            }

            // Update stock status
            if ($rawMaterial->current_stock <= 0) {
                $rawMaterial->stock_status = 'out_of_stock';
            } elseif ($rawMaterial->current_stock <= $rawMaterial->reorder_point) {
                $rawMaterial->stock_status = 'low';
            } else {
                $rawMaterial->stock_status = 'normal';
            }

            $rawMaterial->save();
            DB::commit();

            return redirect()->route('admin.inventory.buffer-stock.raw-materials')
                ->with('success', 'Data bahan baku berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $rawMaterial = MasterItemRawMaterial::findOrFail($id);
            $rawMaterial->delete();
            DB::commit();

            return redirect()->route('admin.inventory.buffer-stock.raw-materials')
                ->with('success', 'Bahan baku berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    public function show($id)
    {
        $rawMaterial = MasterItemRawMaterial::findOrFail($id);
        
        $bomUsage = \App\Models\MasterItemBillOfMaterials::with('item')
            ->where('item_raw_id', $id)
            ->get();
            
        return view('admin_inventory.master_data.raw_materials.show', compact('rawMaterial', 'bomUsage'));
    }
}
