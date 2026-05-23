@extends('layouts.admin_inventory.app')

@section('title', 'Riwayat Penyesuaian Stok')

@section('content')
<div class="min-h-screen py-8" style="background: linear-gradient(135deg,#f8f5ff 0%,#eef2ff 100%);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-7 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 flex items-center gap-2">
                    <span class="text-indigo-600"><i class="bi bi-arrow-left-right"></i></span>
                    Riwayat Penyesuaian Stok
                </h1>
                <p class="text-gray-500 mt-1 text-sm">Pengecekan stok fisik (Stock Opname) vs sistem, cacat, retur, dll.</p>
            </div>
            <div>
                <a href="{{ route('admin.stock-adjustment.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors">
                    <i class="bi bi-plus-lg"></i> Buat Penyesuaian
                </a>
            </div>
        </div>

        <!-- Search Filter -->
        <div class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex justify-between items-center">
            <form action="{{ route('admin.stock-adjustment.index') }}" method="GET" class="w-full md:w-1/2 flex gap-2">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i class="bi bi-search"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari alasan, catatan, atau tipe item..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-indigo-600 focus:border-indigo-600 outline-none transition-all">
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors text-sm font-medium">Cari</button>
            </form>
        </div>



        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-slate-600 uppercase tracking-wider text-xs">Dokumen</th>
                        <th class="px-6 py-4 text-left font-semibold text-slate-600 uppercase tracking-wider text-xs">Tipe Item</th>
                        <th class="px-6 py-4 text-center font-semibold text-slate-600 uppercase tracking-wider text-xs">Stok Sistem</th>
                        <th class="px-6 py-4 text-center font-semibold text-slate-600 uppercase tracking-wider text-xs">Stok Fisik</th>
                        <th class="px-6 py-4 text-center font-semibold text-slate-600 uppercase tracking-wider text-xs">Selisih</th>
                        <th class="px-6 py-4 text-center font-semibold text-slate-600 uppercase tracking-wider text-xs">Alasan</th>
                        <th class="px-6 py-4 text-center font-semibold text-slate-600 uppercase tracking-wider text-xs">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($adjustments as $adj)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 font-mono font-semibold text-indigo-700">{{ $adj->document_number }}</td>
                        <td class="px-6 py-4 text-gray-700">
                            {{ $adj->item_type == 'finished_good' ? 'Produk Jadi' : 'Bahan Baku' }}
                        </td>
                        <td class="px-6 py-4 text-center font-mono text-gray-500">{{ (float) $adj->qty_system }}</td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-gray-800">{{ (float) $adj->qty_physical }}</td>
                        <td class="px-6 py-4 text-center font-mono">
                            @if($adj->qty_difference > 0)
                                <span class="text-green-600 font-semibold">+{{ (float) $adj->qty_difference }}</span>
                            @elseif($adj->qty_difference < 0)
                                <span class="text-red-600 font-semibold">{{ (float) $adj->qty_difference }}</span>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-700 uppercase">
                                @switch($adj->reason)
                                    @case('damaged')
                                        Cacat / Rusak
                                        @break
                                    @case('expired')
                                        Kadaluarsa
                                        @break
                                    @case('missing')
                                        Hilang
                                        @break
                                    @case('system_error')
                                        Error Sistem
                                        @break
                                    @case('manual')
                                        Transaksi
                                        @break
                                    @case('opname_result')
                                        Hasil Opname
                                        @break
                                    @default
                                        Lainnya
                                @endswitch
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-500">{{ $adj->created_at->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">Belum ada data penyesuaian stok.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $adjustments->appends(request()->query())->links() }}
            </div>
        </div>

    </div>
</div>
@endsection
