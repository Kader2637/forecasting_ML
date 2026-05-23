@extends('layouts.admin_inventory.app')

@section('title', 'Tambah Master Data Produk')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <!-- Header Section -->
    <div class="mb-8 flex items-center gap-4">
        <a href="{{ route('admin.inventory.master-items.index') }}" class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Tambah Produk Baru</h1>
            <p class="text-slate-500 mt-2">Masukkan informasi dasar produk. Anda dapat mengatur resep/BOM setelah menyimpan data ini.</p>
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

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <form action="{{ route('admin.inventory.master-items.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="p-8 space-y-6">
                <!-- Kode Produk -->
                <div>
                    <label for="code_item" class="block text-sm font-medium text-slate-700 mb-1">Kode Produk (Opsional)</label>
                    <input type="text" id="code_item" name="code_item" value="{{ old('code_item') }}"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                           placeholder="Contoh: PRD-001">
                </div>

                <!-- Nama Produk -->
                <div>
                    <label for="name_item" class="block text-sm font-medium text-slate-700 mb-1">Nama Produk <span class="text-red-500">*</span></label>
                    <input type="text" id="name_item" name="name_item" required value="{{ old('name_item') }}"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                           placeholder="Masukkan nama produk">
                </div>

                <!-- Kategori Produk -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Kategori Produk <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 p-4 border border-slate-200 rounded-lg bg-slate-50">
                        @forelse($categories as $category)
                            <label class="flex items-center gap-2 cursor-pointer p-2 hover:bg-slate-100 rounded transition-colors">
                                <input type="checkbox" name="category_ids[]" value="{{ $category->category_id }}" 
                                    {{ in_array($category->category_id, old('category_ids', [])) ? 'checked' : '' }}
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
                    <input type="text" id="netweight_item" name="netweight_item" value="{{ old('netweight_item') }}"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                           placeholder="Contoh: 10 ml, 1 Botol, 50 gr">
                    <p class="text-xs text-slate-500 mt-1">Digunakan sebagai referensi satuan untuk produk jadi.</p>
                </div>

                <!-- Status -->
                <div>
                    <label for="status_item" class="block text-sm font-medium text-slate-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="status_item" name="status_item" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white">
                        <option value="active" {{ old('status_item') == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status_item') == 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                    </select>
                </div>

                <!-- Gambar Produk -->
                <div>
                    <label for="picture_item" class="block text-sm font-medium text-slate-700 mb-1">Gambar Produk</label>
                    <input type="file" id="picture_item" name="picture_item" accept="image/*"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white">
                </div>

                <!-- Harga -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="costprice_item" class="block text-sm font-medium text-slate-700 mb-1">Harga Modal (Rp)</label>
                        <input type="number" id="costprice_item" name="costprice_item" value="{{ old('costprice_item') }}" min="0"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label for="sellingprice_item" class="block text-sm font-medium text-slate-700 mb-1">Harga Jual (Rp)</label>
                        <input type="number" id="sellingprice_item" name="sellingprice_item" value="{{ old('sellingprice_item') }}" min="0"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>
                </div>

                <!-- Detail Produk -->
                <div>
                    <label for="description_item" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                    <textarea id="description_item" name="description_item" rows="3"
                              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">{{ old('description_item') }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="ingredient_item" class="block text-sm font-medium text-slate-700 mb-1">Komposisi / Bahan</label>
                        <textarea id="ingredient_item" name="ingredient_item" rows="2"
                                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">{{ old('ingredient_item') }}</textarea>
                    </div>
                    <div>
                        <label for="contain_item" class="block text-sm font-medium text-slate-700 mb-1">Kandungan Tambahan</label>
                        <textarea id="contain_item" name="contain_item" rows="2"
                                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">{{ old('contain_item') }}</textarea>
                    </div>
                </div>

                <!-- Checkbox Reseller Baby Spa -->
                <div class="flex items-center gap-2 mt-2">
                    <input type="checkbox" id="is_reseller_babyspa" name="is_reseller_babyspa" value="1" {{ old('is_reseller_babyspa') ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <label for="is_reseller_babyspa" class="text-sm font-medium text-slate-700">Tersedia untuk Reseller / Baby Spa</label>
                </div>

            </div>
            
            <!-- Footer -->
            <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <a href="{{ route('admin.inventory.master-items.index') }}" class="px-6 py-2 border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-lg font-medium transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                    <i class="bi bi-save"></i>
                    <span>Simpan & Lanjut Setup BOM</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
