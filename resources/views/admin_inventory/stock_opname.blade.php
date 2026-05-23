@extends('layouts.admin_inventory.app')

@section('title', 'Stock Opname & Adjustment')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <i class="bi bi-clipboard-check text-[#696cff]"></i> Stock Opname & Adjustment
            </h1>
            <p class="text-sm text-slate-500 mt-1">Riwayat pemeriksaan dan penyesuaian stok dalam {{ $summary['period_days'] }} hari terakhir.</p>
        </div>
    </div>

    <!-- Live Search -->
    <div class="sneat-card p-6">
        <form action="{{ route('admin.inventory.stock-opname') }}" method="GET" id="search-form">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="relative w-full md:w-1/2">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <i class="bi bi-search"></i>
                </div>
                <input type="text" name="search" id="live-search" value="{{ request('search') }}" 
                       placeholder="Cari item atau material..." 
                       class="w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-[#696cff]/50 focus:border-[#696cff] outline-none transition-all bg-slate-50 focus:bg-white text-slate-600 shadow-sm">
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="sneat-card p-6 border-l-4 border-[#696cff]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Adjustment</p>
                    <p class="text-3xl font-bold text-slate-800 mt-2">{{ $summary['total_adjustments'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-[#e7e7ff] text-[#696cff] flex items-center justify-center text-2xl shadow-sm">
                    <i class="bi bi-bar-chart-fill"></i>
                </div>
            </div>
        </div>

        <div class="sneat-card p-6 border-l-4 border-[#03c3ec]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Materials Adjusted</p>
                    <p class="text-3xl font-bold text-slate-800 mt-2">{{ $materialsWithAdjustments->total() }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-[#d7f5fc] text-[#03c3ec] flex items-center justify-center text-2xl shadow-sm">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>

        <div class="sneat-card p-6 border-l-4 border-[#ffab00]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Period</p>
                    <div class="flex items-baseline gap-1 mt-2">
                        <p class="text-3xl font-bold text-slate-800">{{ $summary['period_days'] }}</p>
                        <p class="text-sm text-slate-500 font-medium">hari</p>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-[#fff2d6] text-[#ffab00] flex items-center justify-center text-2xl shadow-sm">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="sneat-card">
        <div class="flex border-b border-slate-100 overflow-x-auto">
            <a href="{{ route('admin.inventory.stock-opname', ['tab' => 'comparison', 'search' => request('search')]) }}" 
               class="flex-1 px-6 py-4 text-center font-semibold text-sm transition-colors whitespace-nowrap {{ $activeTab === 'comparison' ? 'text-[#696cff] border-b-2 border-[#696cff] bg-[#e7e7ff]/30' : 'text-slate-500 hover:text-[#696cff] hover:bg-slate-50' }}">
                <i class="bi bi-scales"></i> Perbandingan Stok
            </a>
            <a href="{{ route('admin.inventory.stock-opname', ['tab' => 'adjustments', 'search' => request('search')]) }}" 
               class="flex-1 px-6 py-4 text-center font-semibold text-sm transition-colors whitespace-nowrap {{ $activeTab === 'adjustments' ? 'text-[#696cff] border-b-2 border-[#696cff] bg-[#e7e7ff]/30' : 'text-slate-500 hover:text-[#696cff] hover:bg-slate-50' }}">
                <i class="bi bi-clock-history"></i> Adjustment History
            </a>
            <a href="{{ route('admin.inventory.stock-opname', ['tab' => 'materials', 'search' => request('search')]) }}" 
               class="flex-1 px-6 py-4 text-center font-semibold text-sm transition-colors whitespace-nowrap {{ $activeTab === 'materials' ? 'text-[#696cff] border-b-2 border-[#696cff] bg-[#e7e7ff]/30' : 'text-slate-500 hover:text-[#696cff] hover:bg-slate-50' }}">
                <i class="bi bi-boxes"></i> Materials Adjusted
            </a>
            <a href="{{ route('admin.inventory.stock-opname', ['tab' => 'summary', 'search' => request('search')]) }}" 
               class="flex-1 px-6 py-4 text-center font-semibold text-sm transition-colors whitespace-nowrap {{ $activeTab === 'summary' ? 'text-[#696cff] border-b-2 border-[#696cff] bg-[#e7e7ff]/30' : 'text-slate-500 hover:text-[#696cff] hover:bg-slate-50' }}">
                <i class="bi bi-pie-chart"></i> Summary
            </a>
        </div>

        <div class="p-0">
            <!-- TAB: COMPARISON -->
            @if($activeTab === 'comparison')
            <div>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 p-6 bg-slate-50/50 border-b border-slate-100">
                    <div class="text-center md:text-left">
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Checked</p>
                        <p class="text-2xl font-bold text-slate-800 mt-1">{{ $comparisonStats['total_items_checked'] }}</p>
                    </div>
                    <div class="text-center md:text-left">
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Stok Lebih</p>
                        <p class="text-2xl font-bold text-[#71dd37] mt-1">{{ $comparisonStats['items_with_surplus'] }}</p>
                    </div>
                    <div class="text-center md:text-left">
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Stok Kurang</p>
                        <p class="text-2xl font-bold text-[#ff3e1d] mt-1">{{ $comparisonStats['items_with_deficit'] }}</p>
                    </div>
                    <div class="text-center md:text-left">
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Stok Cocok</p>
                        <p class="text-2xl font-bold text-[#696cff] mt-1">{{ $comparisonStats['items_matched'] }}</p>
                    </div>
                    <div class="text-center md:text-left col-span-2 md:col-span-1">
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Selisih</p>
                        <p class="text-2xl font-bold mt-1 {{ $comparisonStats['total_difference'] > 0 ? 'text-[#71dd37]' : 'text-[#ff3e1d]' }}">
                            {{ $comparisonStats['total_difference'] >= 0 ? '+' : '' }}{{ number_format($comparisonStats['total_difference'], 2) }}
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-6 py-4 font-semibold">No</th>
                                <th class="px-6 py-4 font-semibold">Item / Bahan Baku</th>
                                <th class="px-6 py-4 font-semibold text-right">Stok Sistem</th>
                                <th class="px-6 py-4 font-semibold text-right w-40">Stok Fisik</th>
                                <th class="px-6 py-4 font-semibold text-right">Selisih</th>
                                <th class="px-6 py-4 font-semibold text-center">Status</th>
                                <th class="px-6 py-4 font-semibold">Tgl Check</th>
                                <th class="px-6 py-4 font-semibold text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($stockComparison as $idx => $item)
                            @php
                                if ($item->qty_difference > 0) {
                                    $status = 'Lebih';
                                    $statusClass = 'bg-[#e8fadf] text-[#71dd37] border-[#71dd37]/20';
                                } elseif ($item->qty_difference < 0) {
                                    $status = 'Kurang';
                                    $statusClass = 'bg-[#ffe0db] text-[#ff3e1d] border-[#ff3e1d]/20';
                                } else {
                                    $status = 'Cocok';
                                    $statusClass = 'bg-[#e7e7ff] text-[#696cff] border-[#696cff]/20';
                                }
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-slate-500">{{ $stockComparison->firstItem() + $idx }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-800">{{ $item->item_name }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs font-mono text-slate-400">{{ $item->item_code }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $item->item_type === 'raw_material' ? 'bg-[#fff2d6] text-[#ffab00] border-[#ffab00]/20' : 'bg-[#e8fadf] text-[#71dd37] border-[#71dd37]/20' }}">
                                            {{ $item->item_type === 'raw_material' ? 'Bahan Baku' : 'Produk Jadi' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-md font-bold">
                                        {{ number_format($item->qty_system ?? 0, 2) }}
                                    </span>
                                    <p class="text-xs text-slate-400 mt-1">{{ $item->unit }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <input type="number" step="0.01" min="0" 
                                               id="physical-input-{{ $idx }}"
                                               value="{{ number_format($item->qty_physical ?? 0, 2, '.', '') }}" 
                                               class="w-20 text-right px-2 py-1 border border-slate-300 rounded-md focus:ring-2 focus:ring-[#696cff]/50 focus:border-[#696cff] outline-none transition-all font-bold text-sm text-slate-700"
                                               data-idx="{{ $idx }}"
                                               data-item-id="{{ $item->item_id }}"
                                               data-item-type="{{ $item->item_type }}"
                                               data-qty-system="{{ $item->qty_system }}"
                                               data-inventory-id="{{ $item->inventory_id }}"
                                               data-adjustment-id="{{ $item->adjustment_id ?? '' }}"
                                               onchange="updateDifference({{ $idx }})">
                                        <span class="text-xs text-slate-500 font-semibold">{{ $item->unit }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span id="diff-span-{{ $idx }}" class="font-bold text-base {{ $item->qty_difference > 0 ? 'text-[#71dd37]' : ($item->qty_difference < 0 ? 'text-[#ff3e1d]' : 'text-slate-600') }}">
                                        {{ $item->qty_difference > 0 ? '+' : '' }}{{ number_format($item->qty_difference ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span id="status-span-{{ $idx }}" class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ $item->adjusted_at ? ($item->adjusted_at instanceof \Carbon\Carbon ? $item->adjusted_at->format('d M Y H:i') : \Carbon\Carbon::parse($item->adjusted_at)->format('d M Y H:i')) : '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button" 
                                            id="btn-save-{{ $idx }}"
                                            onclick="savePhysicalStock({{ $idx }})"
                                            class="w-full px-3 py-1.5 bg-[#696cff] hover:bg-[#5f61e6] text-white rounded-md text-xs font-bold shadow-sm transition-all flex items-center justify-center gap-1">
                                        <i class="bi bi-check2"></i> Simpan
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i class="bi bi-clipboard-x text-4xl mb-3 text-slate-300"></i>
                                        <p class="text-sm">Tidak ada data perbandingan stok.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($stockComparison->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $stockComparison->links() }}
                </div>
                @endif
            </div>
            @endif

            <!-- TAB: ADJUSTMENTS -->
            @if($activeTab === 'adjustments')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Tanggal</th>
                            <th class="px-6 py-4 font-semibold">Item / Bahan Baku</th>
                            <th class="px-6 py-4 font-semibold">Tipe</th>
                            <th class="px-6 py-4 font-semibold text-right">Qty</th>
                            <th class="px-6 py-4 font-semibold">Alasan</th>
                            <th class="px-6 py-4 font-semibold">Catatan</th>
                            <th class="px-6 py-4 font-semibold">Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($adjustments as $adj)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-xs font-medium text-slate-500">
                                {{ $adj->adjusted_at ? ($adj->adjusted_at instanceof \Carbon\Carbon ? $adj->adjusted_at->format('d M Y H:i') : \Carbon\Carbon::parse($adj->adjusted_at)->format('d M Y H:i')) : '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-800">{{ $adj->display_name }}</p>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold border mt-1 {{ $adj->item_type === 'raw_material' ? 'bg-[#fff2d6] text-[#ffab00] border-[#ffab00]/20' : 'bg-[#e8fadf] text-[#71dd37] border-[#71dd37]/20' }}">
                                    {{ $adj->item_type === 'raw_material' ? 'Bahan Baku' : 'Produk Jadi' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($adj->adjustment_type === 'increase')
                                    <span class="px-2.5 py-1 bg-[#e8fadf] text-[#71dd37] border border-[#71dd37]/20 rounded-full text-xs font-bold">
                                        <i class="bi bi-arrow-up-short"></i> Tambah
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 bg-[#ffe0db] text-[#ff3e1d] border border-[#ff3e1d]/20 rounded-full text-xs font-bold">
                                        <i class="bi bi-arrow-down-short"></i> Kurang
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-slate-800">{{ number_format($adj->qty_difference, 2) }}</span>
                                <span class="text-xs text-slate-500">{{ $adj->unit ?? 'unit' }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $adj->reason }}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $adj->notes ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-md text-xs font-medium">
                                    {{ $adj->adjustedByUser->name ?? 'System' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="bi bi-clock-history text-4xl mb-3 text-slate-300"></i>
                                    <h3 class="text-lg font-bold text-slate-700 mb-1">Tidak ada History Adjustment</h3>
                                    <p class="text-sm">Belum ada riwayat penyesuaian stok yang tercatat dalam periode ini.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($adjustments->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $adjustments->links() }}
            </div>
            @endif
            @endif

            <!-- TAB: MATERIALS -->
            @if($activeTab === 'materials')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Item / Bahan Baku</th>
                            <th class="px-6 py-4 font-semibold text-right">Jml Adjustment</th>
                            <th class="px-6 py-4 font-semibold text-right">Total Qty Adjusted</th>
                            <th class="px-6 py-4 font-semibold text-right">Rata-rata Adjustment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($materialsWithAdjustments as $mat)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-800">{{ $mat->display_name }}</p>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold border mt-1 {{ $mat->item_type === 'raw_material' ? 'bg-[#fff2d6] text-[#ffab00] border-[#ffab00]/20' : 'bg-[#e8fadf] text-[#71dd37] border-[#71dd37]/20' }}">
                                    {{ $mat->item_type === 'raw_material' ? 'Bahan Baku' : 'Produk Jadi' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-2.5 py-1 bg-[#e7e7ff] text-[#696cff] border border-[#696cff]/20 rounded-full text-xs font-bold">
                                    {{ $mat->adjustment_count }}x
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-slate-800">{{ number_format($mat->total_adjustment, 2) }}</span>
                                <span class="text-xs text-slate-500">{{ $mat->display_unit }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-slate-500 font-medium">
                                {{ number_format($mat->total_adjustment / max(1, $mat->adjustment_count), 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="bi bi-boxes text-4xl mb-3 text-slate-300"></i>
                                    <h3 class="text-lg font-bold text-slate-700 mb-1">Tidak ada Material/Produk</h3>
                                    <p class="text-sm">Belum ada material atau produk jadi yang disesuaikan.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($materialsWithAdjustments->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $materialsWithAdjustments->links() }}
            </div>
            @endif
            @endif

            <!-- TAB: SUMMARY -->
            @if($activeTab === 'summary')
            <div class="p-6 space-y-6">
                <!-- Adjustment Types -->
                @if(!empty($summary['adjustment_types']))
                <div>
                    <h3 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="bi bi-pie-chart text-[#696cff]"></i> Adjustment by Type
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($summary['adjustment_types'] as $type => $qty)
                        <div class="border border-slate-200 p-4 rounded-xl flex justify-between items-center bg-slate-50/50">
                            <span class="text-sm font-semibold text-slate-600 capitalize">
                                {{ $type === 'increase' ? '↑ Penambahan (Kelebihan)' : '↓ Pengurangan (Kekurangan)' }}
                            </span>
                            <span class="text-xl font-bold text-slate-800">{{ number_format($qty, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Adjustment Reasons -->
                @if(!empty($summary['adjustment_reasons']))
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="bi bi-journal-text text-[#ffab00]"></i> Adjustment by Reason
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($summary['adjustment_reasons'] as $reason => $qty)
                        <div class="border border-slate-200 p-4 rounded-xl flex justify-between items-center bg-slate-50/50">
                            <span class="text-sm font-semibold text-slate-600">{{ $reason }}</span>
                            <span class="text-xl font-bold text-slate-800">{{ number_format($qty, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
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
            document.getElementById('search-form').submit();
        }, 800);
    });
    // Auto focus and place cursor at end
    const val = searchInput.value;
    if (val) {
        searchInput.focus();
        searchInput.setSelectionRange(val.length, val.length);
    }
}

function number_format(num) {
    if (!num) return '0.00';
    return parseFloat(num).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function updateDifference(idx) {
    const input = document.getElementById(`physical-input-${idx}`);
    const qtyPhysical = parseFloat(input.value) || 0;
    const qtySystem = parseFloat(input.dataset.qtySystem) || 0;
    const diff = qtyPhysical - qtySystem;
    
    // Update discrepancy text
    const diffSpan = document.getElementById(`diff-span-${idx}`);
    diffSpan.textContent = (diff > 0 ? '+' : '') + number_format(diff);
    
    // Update classes on discrepancy
    diffSpan.className = 'font-bold text-base ' + (diff > 0 ? 'text-[#71dd37]' : (diff < 0 ? 'text-[#ff3e1d]' : 'text-slate-600'));
    
    // Update status badge
    const statusSpan = document.getElementById(`status-span-${idx}`);
    let statusText = '';
    let statusClass = 'px-2.5 py-1 rounded-full text-xs font-bold border ';
    if (diff > 0) {
        statusText = 'Lebih';
        statusClass += 'bg-[#e8fadf] text-[#71dd37] border-[#71dd37]/20';
    } else if (diff < 0) {
        statusText = 'Kurang';
        statusClass += 'bg-[#ffe0db] text-[#ff3e1d] border-[#ff3e1d]/20';
    } else {
        statusText = 'Cocok';
        statusClass += 'bg-[#e7e7ff] text-[#696cff] border-[#696cff]/20';
    }
    statusSpan.textContent = statusText;
    statusSpan.className = statusClass;
}

function savePhysicalStock(idx) {
    const input = document.getElementById(`physical-input-${idx}`);
    const btn = document.getElementById(`btn-save-${idx}`);
    const originalBtnHtml = btn.innerHTML;
    
    // Fetch values
    const itemId = input.dataset.itemId;
    const itemType = input.dataset.itemType;
    const qtyPhysical = parseFloat(input.value) || 0;
    const qtySystem = parseFloat(input.dataset.qtySystem) || 0;
    const inventoryId = input.dataset.inventoryId;
    const adjustmentId = input.dataset.adjustmentId;

    // Show loading spinner
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ...`;

    // Make AJAX request
    fetch('{{ route('admin.inventory.stock-opname.save-physical-stock') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            item_id: parseInt(itemId),
            item_type: itemType,
            qty_physical: qtyPhysical,
            qty_system: qtySystem,
            inventory_id: parseInt(inventoryId),
            adjustment_id: adjustmentId ? parseInt(adjustmentId) : null
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            if (data.data && data.data.adjustment_id) {
                input.dataset.adjustmentId = data.data.adjustment_id;
            }
            btn.innerHTML = '<i class="bi bi-check2-all"></i> Berhasil';
            btn.className = "w-full px-3 py-1.5 bg-[#71dd37] text-white rounded-md text-xs font-bold shadow-sm flex items-center justify-center gap-1";
            
            setTimeout(() => {
                btn.innerHTML = originalBtnHtml;
                btn.className = "w-full px-3 py-1.5 bg-[#696cff] hover:bg-[#5f61e6] text-white rounded-md text-xs font-bold shadow-sm transition-all flex items-center justify-center gap-1";
            }, 2000);
        } else {
            alert('Gagal menyimpan: ' + (data.message || 'Terjadi kesalahan'));
            btn.innerHTML = originalBtnHtml;
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalBtnHtml;
        alert('Terjadi kesalahan koneksi');
    });
}
</script>
@endsection
