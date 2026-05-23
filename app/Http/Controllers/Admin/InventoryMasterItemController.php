<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterItem;
use App\Models\MasterCategory;
use App\Models\MasterItemRawMaterial;
use App\Models\MasterItemBillOfMaterials;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryMasterItemController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterItem::with(['categories']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_item', 'LIKE', "%{$search}%")
                  ->orWhere('code_item', 'LIKE', "%{$search}%");
            });
        }

        $items = $query->latest()->paginate(10);
        
        return view('admin_inventory.master_data.items.index', compact('items'));
    }

    public function create()
    {
        $categories = MasterCategory::orderBy('name_category')->get();
        return view('admin_inventory.master_data.items.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_item' => 'required|string|max:255|unique:master_items,name_item',
            'code_item' => 'nullable|string|max:50|unique:master_items,code_item',
            'netweight_item' => 'nullable|string|max:50',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:master_categories,category_id',
            'status_item' => 'required|in:active,inactive',
            'picture_item' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description_item' => 'nullable|string',
            'ingredient_item' => 'nullable|string',
            'contain_item' => 'nullable|string',
            'costprice_item' => 'nullable|numeric|min:0',
            'sellingprice_item' => 'nullable|numeric|min:0',
            'is_reseller_babyspa' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $picturePath = null;
            if ($request->hasFile('picture_item')) {
                $picturePath = $request->file('picture_item')->store('products', 'public');
            }

            $item = MasterItem::create([
                'company_id' => 1,
                'name_item' => $request->name_item,
                'code_item' => $request->code_item,
                'netweight_item' => $request->netweight_item,
                'status_item' => $request->status_item,
                'description_item' => $request->description_item,
                'ingredient_item' => $request->ingredient_item,
                'contain_item' => $request->contain_item,
                'costprice_item' => $request->costprice_item ?? 0,
                'sellingprice_item' => $request->sellingprice_item ?? 0,
                'is_reseller_babyspa' => $request->has('is_reseller_babyspa') ? 1 : 0,
                'picture_item' => $picturePath,
            ]);

            $item->categories()->attach($request->category_ids);

            DB::commit();
            return redirect()->route('admin.inventory.master-items.edit', $item->item_id)->with('success', 'Master Item berhasil ditambahkan. Silakan atur BOM/Resep.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $item = MasterItem::with('categories')->findOrFail($id);
        $categories = MasterCategory::orderBy('name_category')->get();
        
        // Fetch raw materials for BOM selection
        $rawMaterials = MasterItemRawMaterial::orderBy('material_name')->get();

        // Fetch existing BOMs for this item
        $boms = MasterItemBillOfMaterials::with('rawMaterial')
                ->where('item_id', $id)
                ->get();
        
        return view('admin_inventory.master_data.items.edit', compact('item', 'categories', 'rawMaterials', 'boms'));
    }

    public function update(Request $request, $id)
    {
        $item = MasterItem::findOrFail($id);

        $request->validate([
            'name_item' => 'required|string|max:255|unique:master_items,name_item,' . $item->item_id . ',item_id',
            'code_item' => 'nullable|string|max:50|unique:master_items,code_item,' . $item->item_id . ',item_id',
            'netweight_item' => 'nullable|string|max:50',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:master_categories,category_id',
            'status_item' => 'required|in:active,inactive',
            'picture_item' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description_item' => 'nullable|string',
            'ingredient_item' => 'nullable|string',
            'contain_item' => 'nullable|string',
            'costprice_item' => 'nullable|numeric|min:0',
            'sellingprice_item' => 'nullable|numeric|min:0',
            'is_reseller_babyspa' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $updateData = [
                'name_item' => $request->name_item,
                'code_item' => $request->code_item,
                'netweight_item' => $request->netweight_item,
                'status_item' => $request->status_item,
                'description_item' => $request->description_item,
                'ingredient_item' => $request->ingredient_item,
                'contain_item' => $request->contain_item,
                'costprice_item' => $request->costprice_item ?? 0,
                'sellingprice_item' => $request->sellingprice_item ?? 0,
                'is_reseller_babyspa' => $request->has('is_reseller_babyspa') ? 1 : 0,
            ];

            if ($request->hasFile('picture_item')) {
                // Hapus gambar lama jika ada
                if ($item->picture_item && \Illuminate\Support\Facades\Storage::disk('public')->exists($item->picture_item)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($item->picture_item);
                }
                $updateData['picture_item'] = $request->file('picture_item')->store('products', 'public');
            }

            $item->update($updateData);

            $item->categories()->sync($request->category_ids);

            DB::commit();
            return redirect()->route('admin.inventory.master-items.index')->with('success', 'Master Item berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $item = MasterItem::findOrFail($id);
        
        // Cannot delete if there are stocks
        if ($item->stocks()->count() > 0) {
            return back()->with('error', 'Item ini tidak dapat dihapus karena sudah memiliki data stock (Produk Jadi).');
        }

        // Delete related production orders to avoid dangling references
        \App\Models\ProductionOrder::where('item_id', $id)->delete();
        \App\Models\FinishedGoodsIn::where('item_id', $id)->delete();
        \App\Models\FinishedGoodsOut::where('item_id', $id)->delete();

        $item->delete();
        return redirect()->route('admin.inventory.master-items.index')->with('success', 'Master Item berhasil dihapus.');
    }
}
