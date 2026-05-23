@extends('layouts.admin_inventory.app')

@section('title', 'Detail Bahan Baku')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-2">
        <a href="{{ route('admin.inventory.buffer-stock.raw-materials') }}" class="w-10 h-10 rounded-lg bg-white border border-slate-200 text-slate-500 flex items-center justify-center hover:bg-slate-50 hover:text-slate-700 transition-colors shadow-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Detail Bahan Baku</h1>
            <p class="text-sm text-slate-500 mt-1">Informasi detail dari bahan baku #{{ $rawMaterial->item_raw_id }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-8">
        
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-3 mb-5">
            <span class="w-6 h-6 rounded bg-[#e7e7ff] text-[#696cff] flex items-center justify-center text-xs"><i class="bi bi-info-circle-fill"></i></span>
            Informasi Bahan Baku
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="space-y-1">
                <span class="text-sm text-slate-500 font-semibold">Nama Bahan Baku</span>
                <p class="text-lg font-bold text-slate-800">{{ $rawMaterial->material_name }}</p>
            </div>
            
            <div class="space-y-1">
                <span class="text-sm text-slate-500 font-semibold">Satuan (Unit)</span>
                <p class="font-bold text-slate-800"><span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-xs rounded-full border border-slate-200">{{ $rawMaterial->unit }}</span></p>
            </div>
            
            <div class="space-y-1">
                <span class="text-sm text-slate-500 font-semibold">Supplier</span>
                <p class="font-bold text-slate-800"><i class="bi bi-truck mr-1 text-slate-400"></i> {{ $rawMaterial->supplier_name ?: '-' }}</p>
            </div>
            
            <div class="space-y-1">
                <span class="text-sm text-slate-500 font-semibold">Harga Beli</span>
                <p class="font-mono font-bold text-slate-800">Rp {{ number_format($rawMaterial->purchase_price, 0) }}</p>
            </div>
        </div>

        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-3 mb-5">
            <span class="w-6 h-6 rounded bg-[#e8fadf] text-[#71dd37] flex items-center justify-center text-xs"><i class="bi bi-box-seam-fill"></i></span>
            Stok & Rantai Pasok
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block mb-1">Stok Saat Ini</span>
                <p class="text-2xl font-black {{ $rawMaterial->current_stock < $rawMaterial->buffer_stock ? 'text-[#ff3e1d]' : 'text-slate-800' }} mb-2">{{ number_format($rawMaterial->current_stock, 1) }}</p>
                <div class="px-3 py-1.5 rounded-lg border flex items-center gap-2 {{ $rawMaterial->stock_status == 'normal' ? 'bg-[#e8fadf] border-[#71dd37]/30 text-[#71dd37]' : ($rawMaterial->stock_status == 'low' ? 'bg-[#fff2d6] border-[#ffab00]/30 text-[#ffab00]' : 'bg-[#ffe0db] border-[#ff3e1d]/30 text-[#ff3e1d]') }}">
                    <span class="w-2 h-2 rounded-full {{ $rawMaterial->stock_status == 'normal' ? 'bg-[#71dd37]' : ($rawMaterial->stock_status == 'low' ? 'bg-[#ffab00]' : 'bg-[#ff3e1d]') }}"></span>
                    <span class="text-xs font-bold">{{ $rawMaterial->stock_status == 'normal' ? 'STOK AMAN' : ($rawMaterial->stock_status == 'low' ? 'STOK MENIPIS' : 'STOK HABIS') }}</span>
                </div>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block mb-1">Penggunaan Harian</span>
                <p class="text-xl font-bold text-slate-700">{{ number_format($rawMaterial->avg_daily_usage, 2) }}</p>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block mb-1">Lead Time</span>
                <p class="text-xl font-bold text-slate-700">{{ $rawMaterial->lead_time_days }} hari</p>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block mb-1">Buffer Stock</span>
                <p class="text-2xl font-black text-[#696cff]">{{ number_format($rawMaterial->buffer_stock, 1) }}</p>
            </div>
        </div>

        <div class="bg-[#fff2d6] rounded-xl p-4 border border-[#ffab00]/20 flex items-start gap-3 mt-4">
            <i class="bi bi-exclamation-triangle-fill text-[#ffab00] text-xl mt-0.5"></i>
            <div>
                <p class="text-sm font-bold text-[#ffab00] mb-1">Reorder Point</p>
                <p class="text-xs text-slate-700 leading-relaxed">
                    Berdasarkan rata-rata penggunaan harian dan waktu tunggu (lead time), sistem menyarankan batas pengisian ulang (Reorder Point) pada stok: <strong class="text-lg text-slate-800 block mt-1">{{ number_format($rawMaterial->reorder_point, 1) }} {{ $rawMaterial->unit }}</strong>
                </p>
            </div>
        </div>

        <div class="mt-8">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-3 mb-5">
                <span class="w-6 h-6 rounded bg-[#fff2d6] text-[#ffab00] flex items-center justify-center text-xs"><i class="bi bi-diagram-2-fill"></i></span>
                Digunakan Pada Produk (BOM)
            </h3>
            
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Produk Jadi</th>
                            <th class="px-6 py-4 font-semibold text-right">Kebutuhan Bahan Baku</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($bomUsage as $bom)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800">{{ $bom->item->name_item ?? 'Produk Tidak Ditemukan' }}</div>
                                    <div class="text-xs text-slate-400 mt-1">{{ $bom->item->code_item ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-[#696cff]">
                                    {{ number_format($bom->quantity_required, 4) }} <span class="text-xs text-slate-400">{{ $rawMaterial->unit }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-8 text-center text-slate-500">
                                    <i class="bi bi-inbox text-3xl block mb-2 text-slate-300"></i>
                                    Belum ada produk yang menggunakan bahan baku ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('admin.inventory.raw-materials.edit', $rawMaterial->item_raw_id) }}" class="px-6 py-2.5 bg-[#fff2d6] text-[#ffab00] hover:bg-[#ffab00] hover:text-white text-sm font-bold rounded-lg transition-colors flex items-center gap-2">
                <i class="bi bi-pencil"></i> Edit Bahan Baku
            </a>
        </div>
    </div>
</div>
@endsection
