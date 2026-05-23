@extends('layouts.admin_inventory.app')

@section('title', 'Tambah Bahan Baku Baru')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-2">
        <a href="{{ route('admin.inventory.buffer-stock.raw-materials') }}" class="w-10 h-10 rounded-lg bg-white border border-slate-200 text-slate-500 flex items-center justify-center hover:bg-slate-50 hover:text-slate-700 transition-colors shadow-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tambah Bahan Baku</h1>
            <p class="text-sm text-slate-500 mt-1">Isi formulir di bawah ini untuk mendaftarkan bahan baku baru.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-[#ffe0db] border border-[#ff3e1d]/20 text-[#ff3e1d] px-6 py-4 rounded-xl shadow-sm mb-4">
            <div class="flex items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill text-xl mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-sm mb-1">Terdapat Kesalahan Input:</h4>
                    <ul class="text-sm list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-[#ffe0db] border border-[#ff3e1d]/20 text-[#ff3e1d] px-6 py-4 rounded-xl shadow-sm mb-4 flex items-center gap-3">
            <i class="bi bi-x-circle-fill text-xl"></i>
            <span class="font-semibold text-sm">{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ route('admin.inventory.raw-materials.store') }}" method="POST" class="sneat-card p-6 md:p-8">
        @csrf

        <div class="space-y-8">
            <!-- Informasi Dasar -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-3 mb-5">
                    <span class="w-6 h-6 rounded bg-[#e7e7ff] text-[#696cff] flex items-center justify-center text-xs"><i class="bi bi-info-circle-fill"></i></span>
                    Informasi Dasar
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nama Bahan Baku <span class="text-red-500">*</span></label>
                        <input type="text" name="material_name" value="{{ old('material_name') }}" required class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] transition-shadow bg-slate-50 focus:bg-white text-sm" placeholder="Contoh: Ekstrak Chamomile">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Satuan (Unit) <span class="text-red-500">*</span></label>
                        <select name="unit" required class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] transition-shadow bg-slate-50 focus:bg-white text-sm appearance-none">
                            <option value="">Pilih Satuan...</option>
                            <option value="mL" {{ old('unit') == 'mL' ? 'selected' : '' }}>Milliliter (mL)</option>
                            <option value="L" {{ old('unit') == 'L' ? 'selected' : '' }}>Liter (L)</option>
                            <option value="g" {{ old('unit') == 'g' ? 'selected' : '' }}>Gram (g)</option>
                            <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                            <option value="Pcs" {{ old('unit') == 'Pcs' ? 'selected' : '' }}>Pieces (Pcs)</option>
                            <option value="Botol" {{ old('unit') == 'Botol' ? 'selected' : '' }}>Botol</option>
                            <option value="Box" {{ old('unit') == 'Box' ? 'selected' : '' }}>Box</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Supplier</label>
                        <input type="text" name="supplier_name" value="{{ old('supplier_name') }}" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] transition-shadow bg-slate-50 focus:bg-white text-sm" placeholder="Nama Supplier / Vendor">
                    </div>
                </div>
            </div>

            <!-- Stok & Harga -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-3 mb-5">
                    <span class="w-6 h-6 rounded bg-[#e8fadf] text-[#71dd37] flex items-center justify-center text-xs"><i class="bi bi-box-seam-fill"></i></span>
                    Stok & Harga
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Stok Awal</label>
                        <input type="number" step="0.01" min="0" name="current_stock" value="{{ old('current_stock', '0') }}" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] transition-shadow bg-slate-50 focus:bg-white text-sm font-mono" placeholder="0">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Harga Beli</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-sm">Rp</span>
                            <input type="number" step="1" min="0" name="purchase_price" value="{{ old('purchase_price', '0') }}" class="w-full pl-12 pr-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] transition-shadow bg-slate-50 focus:bg-white text-sm font-mono" placeholder="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Konfigurasi Rantai Pasok -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-3 mb-5">
                    <span class="w-6 h-6 rounded bg-[#fff2d6] text-[#ffab00] flex items-center justify-center text-xs"><i class="bi bi-truck"></i></span>
                    Konfigurasi Rantai Pasok (Buffer Stock)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Rata-rata Penggunaan Harian</label>
                        <input type="number" step="0.01" min="0" name="avg_daily_usage" value="{{ old('avg_daily_usage', '0') }}" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] transition-shadow bg-slate-50 focus:bg-white text-sm font-mono" placeholder="0">
                        <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">Estimasi penggunaan bahan baku per hari. Digunakan untuk menghitung Buffer Stock secara otomatis.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Lead Time (Waktu Tunggu)</label>
                        <div class="relative">
                            <input type="number" step="1" min="0" name="lead_time_days" value="{{ old('lead_time_days', '0') }}" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] transition-shadow bg-slate-50 focus:bg-white text-sm font-mono" placeholder="0">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-semibold">Hari</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">Waktu (dalam hari) yang dibutuhkan sejak pesanan dibuat hingga barang tiba.</p>
                    </div>
                </div>
                
                <div class="mt-4 p-4 bg-[#e7e7ff]/50 rounded-lg border border-[#696cff]/20">
                    <div class="flex gap-3">
                        <i class="bi bi-lightbulb-fill text-[#696cff] mt-0.5"></i>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            <strong>Otomatisasi Sistem:</strong> Jika Anda mengisi rata-rata penggunaan dan Lead Time, sistem akan menghitung Buffer Stock dan Reorder Point secara otomatis. Anda dapat melihat hasil perhitungannya nanti di detail bahan baku.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('admin.inventory.buffer-stock.raw-materials') }}" class="px-6 py-2.5 text-slate-600 bg-slate-100 hover:bg-slate-200 text-sm font-bold rounded-lg transition-colors">Batal</a>
            <button type="submit" class="px-6 py-2.5 bg-[#696cff] text-white hover:bg-[#5f61e6] text-sm font-bold rounded-lg shadow-sm transition-colors flex items-center gap-2">
                <i class="bi bi-save"></i> Simpan Bahan Baku
            </button>
        </div>
    </form>
</div>
@endsection
