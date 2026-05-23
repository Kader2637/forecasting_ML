@extends('layouts.admin_inventory.app')

@section('title', 'Edit Master Data Produk')

@section('content')
<div class="p-6 max-w-5xl mx-auto" x-data="{ activeTab: 'detail' }">
    <!-- Header Section -->
    <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.inventory.master-items.index') }}" class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Edit Produk: {{ $item->name_item }}</h1>
                <p class="text-slate-500 mt-1">Kelola detail informasi produk dan resep pembuatannya (BOM).</p>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
            <i class="bi bi-exclamation-triangle-fill text-xl"></i>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="flex border-b border-slate-200 mb-6">
        <button @click="activeTab = 'detail'" 
                :class="{'border-blue-600 text-blue-600': activeTab === 'detail', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300': activeTab !== 'detail'}"
                class="px-6 py-3 font-medium text-sm border-b-2 transition-colors flex items-center gap-2">
            <i class="bi bi-info-circle"></i>
            Detail Produk
        </button>
        <button @click="activeTab = 'bom'" 
                :class="{'border-blue-600 text-blue-600': activeTab === 'bom', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300': activeTab !== 'bom'}"
                class="px-6 py-3 font-medium text-sm border-b-2 transition-colors flex items-center gap-2">
            <i class="bi bi-list-task"></i>
            Resep / Bill of Materials (BOM)
            <span class="ml-2 bg-slate-100 text-slate-600 py-0.5 px-2 rounded-full text-xs border border-slate-200">{{ $boms->count() }}</span>
        </button>
    </div>

    <!-- Tab Content: Detail Produk -->
    <div x-show="activeTab === 'detail'" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <form action="{{ route('admin.inventory.master-items.update', $item->item_id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="p-8 space-y-6">
                <!-- Kode Produk -->
                <div>
                    <label for="code_item" class="block text-sm font-medium text-slate-700 mb-1">Kode Produk (Opsional)</label>
                    <input type="text" id="code_item" name="code_item" value="{{ old('code_item', $item->code_item) }}"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>

                <!-- Nama Produk -->
                <div>
                    <label for="name_item" class="block text-sm font-medium text-slate-700 mb-1">Nama Produk <span class="text-red-500">*</span></label>
                    <input type="text" id="name_item" name="name_item" required value="{{ old('name_item', $item->name_item) }}"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>

                <!-- Kategori Produk -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Kategori Produk <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 p-4 border border-slate-200 rounded-lg bg-slate-50">
                        @php
                            $selectedCategories = old('category_ids', $item->categories->pluck('category_id')->toArray());
                        @endphp
                        @forelse($categories as $category)
                            <label class="flex items-center gap-2 cursor-pointer p-2 hover:bg-slate-100 rounded transition-colors">
                                <input type="checkbox" name="category_ids[]" value="{{ $category->category_id }}" 
                                    {{ in_array($category->category_id, $selectedCategories) ? 'checked' : '' }}
                                    class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <span class="text-sm text-slate-700">{{ $category->name_category }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500 col-span-full">Belum ada kategori. <a href="{{ route('admin.inventory.master-categories.index') }}" class="text-blue-600 hover:underline">Tambah kategori di sini</a>.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Berat Bersih / Unit -->
                <div>
                    <label for="netweight_item" class="block text-sm font-medium text-slate-700 mb-1">Berat Bersih / Isi / Satuan Produk</label>
                    <input type="text" id="netweight_item" name="netweight_item" value="{{ old('netweight_item', $item->netweight_item) }}"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                           placeholder="Contoh: 10 ml, 1 Botol, 50 gr">
                </div>

                <!-- Status -->
                <div>
                    <label for="status_item" class="block text-sm font-medium text-slate-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="status_item" name="status_item" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white">
                        <option value="active" {{ old('status_item', $item->status_item) == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status_item', $item->status_item) == 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                    </select>
                </div>

                <!-- Gambar Produk -->
                <div>
                    <label for="picture_item" class="block text-sm font-medium text-slate-700 mb-1">Gambar Produk</label>
                    @if($item->picture_item)
                        <div class="mb-3">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($item->picture_item) }}" alt="Preview Gambar" class="w-32 h-32 object-cover rounded-lg border border-slate-200">
                        </div>
                    @endif
                    <input type="file" id="picture_item" name="picture_item" accept="image/*"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white">
                    <p class="text-xs text-slate-500 mt-1">Biarkan kosong jika tidak ingin mengubah gambar.</p>
                </div>

                <!-- Harga -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="costprice_item" class="block text-sm font-medium text-slate-700 mb-1">Harga Modal (Rp)</label>
                        <input type="number" id="costprice_item" name="costprice_item" value="{{ old('costprice_item', $item->costprice_item) }}" min="0"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label for="sellingprice_item" class="block text-sm font-medium text-slate-700 mb-1">Harga Jual (Rp)</label>
                        <input type="number" id="sellingprice_item" name="sellingprice_item" value="{{ old('sellingprice_item', $item->sellingprice_item) }}" min="0"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>
                </div>

                <!-- Detail Produk -->
                <div>
                    <label for="description_item" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                    <textarea id="description_item" name="description_item" rows="3"
                              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">{{ old('description_item', $item->description_item) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="ingredient_item" class="block text-sm font-medium text-slate-700 mb-1">Komposisi / Bahan</label>
                        <textarea id="ingredient_item" name="ingredient_item" rows="2"
                                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">{{ old('ingredient_item', $item->ingredient_item) }}</textarea>
                    </div>
                    <div>
                        <label for="contain_item" class="block text-sm font-medium text-slate-700 mb-1">Kandungan Tambahan</label>
                        <textarea id="contain_item" name="contain_item" rows="2"
                                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">{{ old('contain_item', $item->contain_item) }}</textarea>
                    </div>
                </div>

                <!-- Checkbox Reseller Baby Spa -->
                <div class="flex items-center gap-2 mt-2">
                    <input type="checkbox" id="is_reseller_babyspa" name="is_reseller_babyspa" value="1" {{ old('is_reseller_babyspa', $item->is_reseller_babyspa) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <label for="is_reseller_babyspa" class="text-sm font-medium text-slate-700">Tersedia untuk Reseller / Baby Spa</label>
                </div>

            </div>
            
            <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                    <i class="bi bi-save"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tab Content: BOM -->
    <div x-show="activeTab === 'bom'" x-cloak class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-slate-800">Bill of Materials (BOM)</h2>
            <button onclick="openBomModal()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center gap-2 font-medium transition-colors">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah Bahan Baku</span>
            </button>
        </div>

        <div class="p-6">
            <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg mb-6 text-sm flex gap-3">
                <i class="bi bi-info-circle-fill text-lg"></i>
                <div>
                    <p class="font-medium mb-1">Apa itu BOM?</p>
                    <p>Bill of Materials (BOM) adalah daftar bahan baku beserta kuantitas yang dibutuhkan untuk memproduksi <strong>1 satuan</strong> produk ini.</p>
                </div>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-700">
                        <tr>
                            <th class="px-4 py-3 font-semibold w-16">No</th>
                            <th class="px-4 py-3 font-semibold">Nama Bahan Baku</th>
                            <th class="px-4 py-3 font-semibold">Satuan (Unit)</th>
                            <th class="px-4 py-3 font-semibold text-right">Kuantitas per Produk</th>
                            <th class="px-4 py-3 font-semibold w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($boms as $index => $bom)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $bom->rawMaterial->material_name ?? 'Bahan terhapus' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 bg-slate-100 rounded text-xs border border-slate-200">{{ $bom->rawMaterial->unit ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ number_format($bom->quantity, 2) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="openEditBomModal({{ $bom->bom_id }}, {{ $bom->raw_material_id }}, {{ $bom->quantity }})" 
                                                class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form action="{{ route('admin.inventory.bom.destroy', $bom->bom_id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus bahan baku ini dari resep?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="bi bi-receipt text-4xl mb-3 text-slate-300"></i>
                                        <p>BOM belum dikonfigurasi. Produk ini belum memiliki resep bahan baku.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add BOM Modal -->
<div id="bomModal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-purple-50/50">
            <h3 class="text-lg font-semibold text-slate-800">Tambah Bahan Baku ke Resep</h3>
            <button onclick="closeBomModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>
        <form action="{{ route('admin.inventory.bom.store', $item->item_id) }}" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label for="raw_material_id" class="block text-sm font-medium text-slate-700 mb-1">Pilih Bahan Baku <span class="text-red-500">*</span></label>
                    <select id="raw_material_id" name="raw_material_id" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all">
                        <option value="">-- Pilih Bahan Baku --</option>
                        @foreach($rawMaterials as $rm)
                            <option value="{{ $rm->raw_material_id }}" data-unit="{{ $rm->unit }}">{{ $rm->material_name }} ({{ $rm->unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="quantity" class="block text-sm font-medium text-slate-700 mb-1">Kuantitas per Produk <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-2">
                        <input type="number" id="quantity" name="quantity" required step="0.01" min="0.01"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all"
                               placeholder="Contoh: 0.5">
                        <span id="unitDisplay" class="bg-slate-100 px-3 py-2 rounded border border-slate-200 text-slate-600 text-sm font-medium w-16 text-center">-</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Jumlah bahan yang dibutuhkan untuk membuat 1 {{ $item->name_item }}.</p>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeBomModal()" class="px-4 py-2 text-slate-600 hover:bg-slate-200 bg-slate-100 rounded-lg font-medium transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors">
                    Tambah
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit BOM Modal -->
<div id="editBomModal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-purple-50/50">
            <h3 class="text-lg font-semibold text-slate-800">Edit Kuantitas Bahan Baku</h3>
            <button onclick="closeEditBomModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>
        <form id="editBomForm" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-4">
                <div>
                    <label for="edit_raw_material_id" class="block text-sm font-medium text-slate-700 mb-1">Pilih Bahan Baku <span class="text-red-500">*</span></label>
                    <select id="edit_raw_material_id" name="raw_material_id" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all">
                        @foreach($rawMaterials as $rm)
                            <option value="{{ $rm->raw_material_id }}" data-unit="{{ $rm->unit }}">{{ $rm->material_name }} ({{ $rm->unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit_quantity" class="block text-sm font-medium text-slate-700 mb-1">Kuantitas per Produk <span class="text-red-500">*</span></label>
                    <input type="number" id="edit_quantity" name="quantity" required step="0.01" min="0.01"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all">
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeEditBomModal()" class="px-4 py-2 text-slate-600 hover:bg-slate-200 bg-slate-100 rounded-lg font-medium transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Include Alpine.js if not already in layout -->
<script src="//unpkg.com/alpinejs" defer></script>

<script>
    function openBomModal() {
        document.getElementById('bomModal').classList.remove('hidden');
    }

    function closeBomModal() {
        document.getElementById('bomModal').classList.add('hidden');
    }

    function openEditBomModal(bomId, rawMaterialId, quantity) {
        document.getElementById('edit_raw_material_id').value = rawMaterialId;
        document.getElementById('edit_quantity').value = quantity;
        document.getElementById('editBomForm').action = `/inventory/bom/${bomId}`;
        document.getElementById('editBomModal').classList.remove('hidden');
    }

    function closeEditBomModal() {
        document.getElementById('editBomModal').classList.add('hidden');
    }

    // Dynamic Unit display in Add BOM modal
    document.getElementById('raw_material_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const unit = selectedOption.getAttribute('data-unit') || '-';
        document.getElementById('unitDisplay').textContent = unit;
    });
</script>
@endsection
