@extends('layouts.admin_inventory.app')

@section('title', 'Detail Buffer Stock Produk')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-2">
        <a href="{{ route('admin.inventory.finished-goods') }}" class="w-10 h-10 rounded-lg bg-white border border-slate-200 text-slate-500 flex items-center justify-center hover:bg-slate-50 hover:text-slate-700 transition-colors shadow-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Detail Produk Jadi & Buffer Stock</h1>
            <p class="text-sm text-slate-500 mt-1">Informasi komprehensif stok, BOM, dan forecasting produk</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        
        <!-- Info Utama Produk -->
        <div class="p-6 md:p-8 bg-gradient-to-br from-blue-50 to-indigo-50/30 border-b border-slate-100">
            <div class="flex items-start justify-between">
                <div>
                    <span class="px-2.5 py-1 bg-white text-indigo-600 font-bold text-xs rounded-full border border-indigo-100 mb-3 inline-block">
                        {{ $itemStock->item->category->name_category ?? 'Tanpa Kategori' }}
                    </span>
                    <h2 class="text-3xl font-black text-slate-800">{{ $itemStock->item->name_item ?? 'Produk Tidak Diketahui' }}</h2>
                    <p class="text-slate-500 font-mono mt-1 flex items-center gap-2">
                        <i class="bi bi-upc-scan"></i> {{ $itemStock->item->code_item ?? '-' }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Lokasi Gudang</p>
                    <p class="text-lg font-bold text-slate-800 flex items-center justify-end gap-2">
                        <i class="bi bi-shop text-indigo-400"></i> {{ $itemStock->inventory->name_inventory ?? '-' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-8">
            <!-- Grid Status Stok -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-slate-50 rounded-xl p-5 border border-slate-100">
                    <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block mb-1">Stok Saat Ini</span>
                    <p class="text-3xl font-black text-slate-800">{{ number_format($itemStock->stock) }} <span class="text-sm text-slate-500 font-semibold">Unit</span></p>
                </div>
                
                <div class="bg-slate-50 rounded-xl p-5 border border-slate-100">
                    <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block mb-1">Buffer Stock</span>
                    <p class="text-3xl font-black text-[#696cff]">{{ number_format($bufferStock) }} <span class="text-sm text-slate-500 font-semibold">Unit</span></p>
                </div>

                <div class="rounded-xl p-5 border {{ $needsOrder ? 'bg-[#ffe0db] border-[#ff3e1d]/30' : 'bg-[#e8fadf] border-[#71dd37]/30' }}">
                    <span class="text-xs font-bold uppercase tracking-wider block mb-1 {{ $needsOrder ? 'text-[#ff3e1d]' : 'text-[#71dd37]' }}">Status Inventori</span>
                    <div class="flex items-center gap-3">
                        <i class="bi {{ $needsOrder ? 'bi-exclamation-triangle-fill text-[#ff3e1d]' : 'bi-check-circle-fill text-[#71dd37]' }} text-3xl"></i>
                        <div>
                            <p class="text-xl font-bold {{ $needsOrder ? 'text-[#ff3e1d]' : 'text-[#71dd37]' }}">
                                {{ $needsOrder ? 'Perlu Produksi' : 'Stok Aman' }}
                            </p>
                            <p class="text-sm font-semibold mt-0.5 {{ $needsOrder ? 'text-[#ff3e1d]/80' : 'text-[#71dd37]/80' }}">
                                Selisih: {{ number_format($stockDifference) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forecasting & ML -->
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-3 mb-5">
                <span class="w-6 h-6 rounded bg-[#e7e7ff] text-[#696cff] flex items-center justify-center text-xs"><i class="bi bi-cpu-fill"></i></span>
                Demand Forecasting (ARIMA)
            </h3>

            @if($forecastSummary)
                <div class="bg-slate-50 rounded-xl border border-slate-200 p-5 mb-8">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 mb-1">Model ARIMA</p>
                            <p class="font-mono font-bold text-slate-800">{{ $forecastSummary->arima_order }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-500 mb-1">Tingkat Error (MAE)</p>
                            <p class="font-mono font-bold text-slate-800">{{ number_format($forecastSummary->mae, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-500 mb-1">Akurasi (MAPE)</p>
                            <p class="font-mono font-bold text-slate-800">{{ number_format($forecastSummary->mape_percentage, 2) }}%</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-500 mb-1">Kategori Performa</p>
                            @if($forecastSummary->kategori_mae == 'rendah')
                                <span class="px-2.5 py-1 bg-[#e8fadf] text-[#71dd37] font-bold text-[10px] uppercase rounded-full border border-[#71dd37]/30">Sangat Baik</span>
                            @elseif($forecastSummary->kategori_mae == 'menengah')
                                <span class="px-2.5 py-1 bg-[#fff2d6] text-[#ffab00] font-bold text-[10px] uppercase rounded-full border border-[#ffab00]/30">Cukup</span>
                            @else
                                <span class="px-2.5 py-1 bg-[#ffe0db] text-[#ff3e1d] font-bold text-[10px] uppercase rounded-full border border-[#ff3e1d]/30">Buruk</span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-200 text-sm text-slate-600 flex items-start gap-2">
                        <i class="bi bi-info-circle text-[#696cff] mt-0.5"></i>
                        <p>Model ini menggunakan riwayat penjualan untuk memprediksi kebutuhan stok masa depan. Rekomendasi Buffer Stock di atas (<strong>{{ number_format($bufferStock) }}</strong>) turut menyesuaikan tren permintaan dari kalkulasi ini.</p>
                    </div>
                </div>
            @else
                <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 mb-8 text-center">
                    <i class="bi bi-cpu text-4xl text-slate-300 mb-3 block"></i>
                    <p class="text-slate-600 font-medium">Belum ada data Machine Learning (ARIMA) untuk produk ini.</p>
                    <p class="text-sm text-slate-500 mt-1">Sistem membutuhkan setidaknya 30 hari histori transaksi (Finished Goods Out) untuk kalkulasi ML yang akurat.</p>
                </div>
            @endif

            @if(isset($forecastDetails) && $forecastDetails->count() > 0)
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-3 mb-5 mt-8">
                    <span class="w-6 h-6 rounded bg-[#e7e7ff] text-[#696cff] flex items-center justify-center text-xs"><i class="bi bi-calendar2-week-fill"></i></span>
                    Detail Prediksi 30 Hari Kedepan
                </h3>
                
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-8">
                    <div class="overflow-x-auto max-h-[400px]">
                        <table class="w-full text-left text-sm text-slate-600 border-collapse relative">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 text-[11px] font-bold uppercase tracking-wider sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-6 py-4">Tanggal Prediksi</th>
                                    <th class="px-6 py-4 text-right">Prediksi Kebutuhan (Unit)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($forecastDetails as $detail)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-3 font-medium text-slate-700">
                                            {{ \Carbon\Carbon::parse($detail->date)->translatedFormat('l, d F Y') }}
                                        </td>
                                        <td class="px-6 py-3 text-right font-mono text-[#696cff] font-bold">
                                            {{ number_format($detail->predicted_sales, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- BOM Details -->
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-3 mb-5 mt-8">
                <span class="w-6 h-6 rounded bg-[#fff2d6] text-[#ffab00] flex items-center justify-center text-xs"><i class="bi bi-diagram-3-fill"></i></span>
                Bill of Materials (Kebutuhan Bahan Baku)
            </h3>

            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Nama Bahan Baku</th>
                                <th class="px-6 py-4 text-center">Satuan</th>
                                <th class="px-6 py-4 text-right">Kebutuhan per Unit</th>
                                <th class="px-6 py-4 text-right">Total Kebutuhan (Utk {{ number_format($bufferStock) }} Unit Buffer)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($bomList as $bom)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-800">{{ $bom->rawMaterial->material_name ?? 'Bahan Tidak Diketahui' }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 bg-slate-100 border border-slate-200 text-slate-700 text-xs font-semibold rounded">{{ $bom->rawMaterial->unit ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono text-[#696cff] font-medium">{{ number_format($bom->quantity_required, 4) }}</td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-slate-800">
                                        {{ number_format($bom->quantity_required * $bufferStock, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                        <i class="bi bi-inbox text-4xl block mb-3 text-slate-300"></i>
                                        Produk ini belum memiliki Bill of Materials (BOM) yang terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-end">
                <a href="{{ route('admin.inventory.forecasting.demand') }}" class="px-6 py-2.5 bg-[#e7e7ff] text-[#696cff] hover:bg-[#696cff] hover:text-white text-sm font-bold rounded-lg transition-colors flex items-center gap-2">
                    <i class="bi bi-bar-chart-fill"></i> Lihat Detail Prediksi ARIMA
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
