<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterCategory;
use Illuminate\Http\Request;

class InventoryCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterCategory::query();

        if ($request->filled('search')) {
            $query->where('name_category', 'LIKE', '%' . $request->search . '%');
        }

        // 10 items per page as requested by user
        $categories = $query->latest('created_at')->paginate(10);
        return view('admin_inventory.master_data.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_category' => 'required|string|max:255|unique:master_categories,name_category'
        ]);

        MasterCategory::create([
            'name_category' => $request->name_category,
        ]);

        return redirect()->route('admin.inventory.master-categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $category = MasterCategory::findOrFail($id);

        $request->validate([
            'name_category' => 'required|string|max:255|unique:master_categories,name_category,' . $category->category_id . ',category_id'
        ]);

        $category->update([
            'name_category' => $request->name_category,
        ]);

        return redirect()->route('admin.inventory.master-categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $category = MasterCategory::findOrFail($id);
        
        // Cek apakah kategori sedang digunakan oleh item (opsional, tergantung logic sistem)
        if ($category->items()->count() > 0) {
            return redirect()->route('admin.inventory.master-categories.index')->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.');
        }

        $category->delete();

        return redirect()->route('admin.inventory.master-categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
