@extends('layouts.admin_inventory.app')

@section('title', 'Master Data Bahan Baku')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-100">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-[#e7e7ff] text-[#696cff] flex items-center justify-center shadow-sm">
                    <i class="bi bi-box-seam text-xl"></i>
                </span>
                Master Data Bahan Baku
            </h1>
            <p class="text-sm text-slate-500 mt-2 max-w-2xl leading-relaxed">Kelola daftar bahan baku produksi, stok awal, dan satuan ukurnya (Unit).</p>
        </div>
        <a href="{{ route('admin.inventory.raw-materials.create') }}" class="px-5 py-2.5 bg-[#696cff] hover:bg-[#5f61e6] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-2">
            <i class="bi bi-plus-lg"></i> Tambah Bahan Baku
        </a>
    </div>



    <!-- Data Table -->
    <div class="sneat-card">
        <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h5 class="text-base font-bold text-slate-800">Daftar Bahan Baku</h5>
            
            <form action="{{ route('admin.inventory.buffer-stock.raw-materials') }}" method="GET" class="w-full sm:w-auto">
                <div class="relative max-w-sm">
                    <input type="text" name="search" value="{{ $search }}" class="w-full pl-10 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] transition-shadow placeholder-slate-400" placeholder="Cari nama atau supplier...">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm text-slate-600">
                <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                    <tr>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Bahan Baku</th>
                        <th class="px-6 py-4">Satuan</th>
                        <th class="px-6 py-4">Harga Beli</th>
                        <th class="px-6 py-4">Stok / Status</th>
                        <th class="px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rawMaterials as $item)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-500">
                                #{{ $item->item_raw_id }}
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-800">{{ $item->material_name }}</p>
                                @if($item->supplier_name)
                                    <p class="text-xs text-slate-500 mt-1"><i class="bi bi-truck mr-1"></i> {{ $item->supplier_name }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-xs rounded-full border border-slate-200">{{ $item->unit }}</span>
                            </td>
                            <td class="px-6 py-4 font-mono font-semibold text-slate-700">
                                Rp {{ number_format($item->purchase_price, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1.5 items-start">
                                    <span class="font-extrabold text-slate-800 text-base">{{ number_format($item->current_stock, 1) }}</span>
                                    
                                    @if($item->stock_status == 'normal')
                                        <span class="px-2 py-0.5 bg-[#e8fadf] text-[#71dd37] border border-[#71dd37]/20 text-[10px] font-bold rounded-full uppercase tracking-wider">Aman</span>
                                    @elseif($item->stock_status == 'low')
                                        <span class="px-2 py-0.5 bg-[#fff2d6] text-[#ffab00] border border-[#ffab00]/20 text-[10px] font-bold rounded-full uppercase tracking-wider">Menipis</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-[#ffe0db] text-[#ff3e1d] border border-[#ff3e1d]/20 text-[10px] font-bold rounded-full uppercase tracking-wider">Habis</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.inventory.raw-materials.edit', $item->item_raw_id) }}" class="w-8 h-8 rounded-lg bg-[#e7e7ff] text-[#696cff] hover:bg-[#696cff] hover:text-white flex items-center justify-center transition-colors tooltip" data-tip="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.inventory.raw-materials.destroy', $item->item_raw_id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus bahan baku ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-[#ffe0db] text-[#ff3e1d] hover:bg-[#ff3e1d] hover:text-white flex items-center justify-center transition-colors tooltip" data-tip="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-500">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-3xl mb-4 text-slate-400">
                                        <i class="bi bi-inbox"></i>
                                    </div>
                                    <p class="font-bold text-slate-700">Tidak ada bahan baku</p>
                                    <p class="text-sm mt-1">Belum ada bahan baku yang terdaftar di database.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-6 border-t border-slate-100">
            {{ $rawMaterials->links('pagination::tailwind') }}
        </div>
    </div>
</div>
@endsection
