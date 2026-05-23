@extends('layouts.admin_inventory.app')

@section('title', 'Daftar Produksi')

@section('content')
<div class="min-h-screen py-8" style="background: linear-gradient(135deg,#f8f5ff 0%,#eef2ff 100%);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-7 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 flex items-center gap-2">
                    <span class="text-purple-600"><i class="bi bi-box-seam"></i></span>
                    Daftar Produksi
                </h1>
                <p class="text-gray-500 mt-1 text-sm">Riwayat produksi produk jadi dari bahan baku.</p>
            </div>
            <div>
                <a href="{{ route('admin.production.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors">
                    <i class="bi bi-plus-lg"></i> Buat Produksi Baru
                </a>
            </div>
        </div>



        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-slate-600 uppercase tracking-wider text-xs">Kode Produksi</th>
                        <th class="px-6 py-4 text-left font-semibold text-slate-600 uppercase tracking-wider text-xs">Produk</th>
                        <th class="px-6 py-4 text-right font-semibold text-slate-600 uppercase tracking-wider text-xs">Kuantitas</th>
                        <th class="px-6 py-4 text-center font-semibold text-slate-600 uppercase tracking-wider text-xs">Tanggal</th>
                        <th class="px-6 py-4 text-center font-semibold text-slate-600 uppercase tracking-wider text-xs">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($productionOrders as $po)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 font-mono font-semibold text-purple-700">{{ $po->order_number }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-800">{{ $po->item->name_item ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 text-right font-mono font-semibold text-gray-700">{{ $po->qty_produced }}</td>
                        <td class="px-6 py-4 text-center text-gray-500">{{ $po->created_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($po->status == 'completed')
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ ucfirst($po->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">Belum ada riwayat produksi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $productionOrders->links() }}
            </div>
        </div>

    </div>
</div>
@endsection
