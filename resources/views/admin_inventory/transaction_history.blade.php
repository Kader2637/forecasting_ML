@extends('layouts.admin_inventory.app')

@section('title', 'History Transaksi Penjualan')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- ===== Header ===== --}}
        <div class="mb-8 flex items-start justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-4xl font-extrabold text-gray-900 mb-2 flex items-center gap-3">
                    <span class="text-indigo-600"><i class="bi bi-clock-history"></i></span>
                    History Transaksi
                </h1>
                <p class="text-lg text-gray-600">Riwayat lengkap seluruh transaksi penjualan pelanggan dan status pembayaran real-time</p>
            </div>
        </div>

        {{-- ===== Statistics Grid ===== --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Pendapatan</p>
                        <p class="text-2xl font-extrabold text-slate-900">Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-2xl">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Transaksi Lunas</p>
                        <p class="text-2xl font-extrabold text-green-600">{{ $summary['lunas'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-2xl">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Belum Lunas / Sebagian</p>
                        <p class="text-2xl font-extrabold text-amber-600">{{ $summary['belum_bayar'] + $summary['sebagian'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Dibatalkan</p>
                        <p class="text-2xl font-extrabold text-red-600">{{ $summary['cancelled'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-xl flex items-center justify-center text-2xl">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Search Filter ===== --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6 border border-slate-100">
            <form method="GET" action="{{ route('admin.inventory.transaction-history') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex-1 flex flex-col md:flex-row gap-3 w-full">
                    <select
                        name="per_page"
                        onchange="this.form.submit()"
                        class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 w-full md:w-auto"
                    >
                        @foreach([10, 15, 25, 50, 100] as $option)
                            <option value="{{ $option }}" {{ (int) $perPage === $option ? 'selected' : '' }}>
                                {{ $option }}/hal
                            </option>
                        @endforeach
                    </select>
                    
                    <div class="relative flex-1 w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-search text-gray-400"></i>
                        </div>
                        <input
                            type="text"
                            name="search"
                            id="live-search"
                            value="{{ $search }}"
                            placeholder="Cari transaksi berdasarkan No. Faktur, alamat, atau pelanggan..."
                            class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                    </div>
                    
                    @if($search)
                        <a href="{{ route('admin.inventory.transaction-history') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold transition-colors flex items-center justify-center w-full md:w-auto">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ===== Main Data Table ===== --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50 text-slate-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">No. Faktur</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Pelanggan</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider">Total Belanja</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider">Ongkir</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider">Grand Total</th>
                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider">Status Pembayaran</th>
                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse($transactions as $row)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-700">{{ $row->number }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    {{ $row->date ? $row->date->format('d M Y H:i') : '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $row->customer->name_customer ?? 'Pelanggan Umum' }}</div>
                                    <div class="text-xs text-slate-500">{{ $row->whatsapp ?? $row->customer->whatsapp_customer ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-medium text-sm">
                                    Rp {{ number_format($row->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-slate-500 text-sm">
                                    Rp {{ number_format($row->shipping_cost ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 text-sm">
                                    Rp {{ number_format($row->grand_total, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $paymentStatus = $row->payment_status;
                                        $overallStatus = $row->overall_status;
                                        
                                        if ($row->shipping_status === 'cancelled') {
                                            $badgeClass = 'bg-red-100 text-red-800';
                                            $labelText = 'Dibatalkan';
                                        } elseif ($paymentStatus === 'lunas') {
                                            $badgeClass = 'bg-green-100 text-green-800';
                                            $labelText = 'Lunas';
                                        } elseif ($paymentStatus === 'sebagian') {
                                            $badgeClass = 'bg-blue-100 text-blue-800';
                                            $labelText = 'Dibayar Sebagian';
                                        } else {
                                            $badgeClass = 'bg-yellow-100 text-yellow-800';
                                            $labelText = 'Belum Bayar';
                                        }
                                    @endphp
                                    <span class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-full {{ $badgeClass }}">
                                        {{ $labelText }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a
                                        href="{{ route('admin.inventory.transaction-history.detail', $row->transaction_sales_id) }}"
                                        class="px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 mx-auto w-fit"
                                    >
                                        <i class="bi bi-eye-fill"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                    <div class="text-5xl mb-3"><i class="bi bi-inbox"></i></div>
                                    <p class="font-medium text-slate-500">Tidak ada data transaksi</p>
                                    <p class="text-xs text-slate-400 mt-1">Coba gunakan filter pencarian lainnya</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $transactions->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<script>
    // Live Search Debounce
    let searchTimeout = null;
    const searchInput = document.getElementById('live-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 800);
        });
        
        // Auto focus and place cursor at end
        const val = searchInput.value;
        if (val) {
            searchInput.focus();
            searchInput.setSelectionRange(val.length, val.length);
        }
    }
</script>
@endsection
