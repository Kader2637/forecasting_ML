@extends('layouts.admin_inventory.app')

@section('title', 'Perbandingan Stok Fisik & Gudang - Gentle Living')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-100">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-[#e7e7ff] text-[#696cff] flex items-center justify-center shadow-sm">
                    <i class="bi bi-clipboard-check text-xl"></i>
                </span>
                Perbandingan Stok Fisik & Gudang
            </h1>
            <p class="text-sm text-slate-500 mt-2 max-w-2xl leading-relaxed">
                {{ $comparisonStats['branch_name'] }} - Total {{ $comparisonStats['total_items_checked'] }} Item
            </p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="sneat-card p-5 border-b-4 border-[#696cff]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Items</p>
                    <p class="text-2xl font-extrabold text-[#696cff]">{{ $comparisonStats['total_items_checked'] }}</p>
                </div>
                <div class="w-10 h-10 rounded bg-[#e7e7ff] text-[#696cff] flex items-center justify-center text-xl shadow-sm">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>

        <div class="sneat-card p-5 border-b-4 border-[#71dd37]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Stok Lebih</p>
                    <p class="text-2xl font-extrabold text-[#71dd37]">{{ $comparisonStats['items_with_surplus'] }}</p>
                </div>
                <div class="w-10 h-10 rounded bg-[#e8fadf] text-[#71dd37] flex items-center justify-center text-xl shadow-sm">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
            </div>
        </div>

        <div class="sneat-card p-5 border-b-4 border-[#ff3e1d]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Stok Kurang</p>
                    <p class="text-2xl font-extrabold text-[#ff3e1d]">{{ $comparisonStats['items_with_deficit'] }}</p>
                </div>
                <div class="w-10 h-10 rounded bg-[#ffe0db] text-[#ff3e1d] flex items-center justify-center text-xl shadow-sm">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
            </div>
        </div>

        <div class="sneat-card p-5 border-b-4 border-[#03c3ec]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Stok Cocok</p>
                    <p class="text-2xl font-extrabold text-[#03c3ec]">{{ $comparisonStats['items_matched'] }}</p>
                </div>
                <div class="w-10 h-10 rounded bg-[#d7f5fc] text-[#03c3ec] flex items-center justify-center text-xl shadow-sm">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="sneat-card p-5 border-b-4 border-slate-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Selisih</p>
                    <p class="text-2xl font-extrabold {{ $comparisonStats['total_difference'] > 0 ? 'text-[#71dd37]' : ($comparisonStats['total_difference'] < 0 ? 'text-[#ff3e1d]' : 'text-slate-600') }}">
                        {{ $comparisonStats['total_difference'] >= 0 ? '+' : '' }}{{ number_format($comparisonStats['total_difference'], 0) }}
                    </p>
                </div>
                <div class="w-10 h-10 rounded bg-slate-100 text-slate-600 flex items-center justify-center text-xl shadow-sm">
                    <i class="bi bi-calculator"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Comparison Table -->
    <div class="sneat-card">
        <!-- Instructions -->
        <div class="bg-[#d7f5fc] border-b border-[#03c3ec]/20 px-6 py-4 flex items-center gap-3">
            <i class="bi bi-info-circle-fill text-[#03c3ec] text-lg"></i>
            <p class="text-sm text-[#03c3ec] font-medium">
                <strong>Instruksi:</strong> Masukkan stok fisik hasil penghitungan di kolom "Stok Fisik" dan klik tombol simpan untuk menyimpan perubahan.
            </p>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm text-slate-600">
                <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs">
                    <tr>
                        <th class="px-6 py-4 font-semibold">No</th>
                        <th class="px-6 py-4 font-semibold">Kode Item</th>
                        <th class="px-6 py-4 font-semibold">Nama Item</th>
                        <th class="px-6 py-4 font-semibold text-right">Stok Gudang</th>
                        <th class="px-6 py-4 font-semibold text-right">Stok Fisik (Edit)</th>
                        <th class="px-6 py-4 font-semibold text-right">Selisih</th>
                        <th class="px-6 py-4 font-semibold text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="stockTable">
                    @forelse($stockComparison as $idx => $item)
                        @php
                            $status = '';
                            $statusColor = '';
                            $statusBg = '';
                            
                            if ($item->qty_difference > 0) {
                                $status = '<i class="bi bi-arrow-up-circle mr-1"></i> Lebih';
                                $statusColor = 'text-[#71dd37]';
                                $statusBg = 'bg-[#e8fadf]';
                            } elseif ($item->qty_difference < 0) {
                                $status = '<i class="bi bi-arrow-down-circle mr-1"></i> Kurang';
                                $statusColor = 'text-[#ff3e1d]';
                                $statusBg = 'bg-[#ffe0db]';
                            } else {
                                $status = '<i class="bi bi-check-circle mr-1"></i> Cocok';
                                $statusColor = 'text-[#03c3ec]';
                                $statusBg = 'bg-[#d7f5fc]';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors" id="row-{{ $idx }}">
                            <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ $idx + 1 }}</td>
                            <td class="px-6 py-4 text-sm">
                                <code class="bg-[#e7e7ff] text-[#696cff] px-2 py-1 rounded text-xs font-bold">{{ $item->item_code }}</code>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-800">{{ $item->item_name }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-3 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-full text-sm font-bold shadow-sm">
                                    {{ number_format($item->qty_system, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <input 
                                    type="number" 
                                    step="0.01"
                                    class="physical-stock-input border border-slate-300 rounded-lg px-3 py-2 text-sm font-bold text-slate-700 text-right w-24 focus:outline-none focus:border-[#696cff] focus:ring-1 focus:ring-[#696cff] transition-shadow shadow-sm"
                                    value="{{ number_format($item->qty_physical, 2, '.', '') }}"
                                    data-item-id="{{ $item->item_id }}"
                                    data-adjustment-id="{{ $item->adjustment_id }}"
                                    data-inventory-id="{{ $item->inventory_id }}"
                                    data-qty-system="{{ $item->qty_system }}"
                                    id="physical-{{ $idx }}"
                                />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-extrabold text-base selisih-value {{ $item->qty_difference > 0 ? 'text-[#71dd37]' : ($item->qty_difference < 0 ? 'text-[#ff3e1d]' : 'text-slate-600') }}" data-original="{{ $item->qty_difference }}">
                                    {{ $item->qty_difference > 0 ? '+' : '' }}{{ number_format($item->qty_difference, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1.5 {{ $statusBg }} {{ $statusColor }} rounded-full text-xs font-bold status-badge inline-flex items-center border {{ $item->qty_difference > 0 ? 'border-[#71dd37]/20' : ($item->qty_difference < 0 ? 'border-[#ff3e1d]/20' : 'border-[#03c3ec]/20') }}">
                                    {!! $status !!}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button 
                                    onclick="savePhysicalStock({{ $idx }})"
                                    class="px-4 py-2 bg-[#696cff] text-white rounded-lg hover:bg-[#5f61e6] text-sm font-bold shadow-sm save-btn transition-colors flex items-center justify-center gap-2 mx-auto"
                                    id="save-{{ $idx }}"
                                >
                                    <i class="bi bi-save"></i> Simpan
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-2xl mb-3">
                                        <i class="bi bi-inbox"></i>
                                    </div>
                                    <p class="font-medium">Tidak ada data stok untuk ditampilkan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Legend -->
        <div class="bg-slate-50 px-6 py-5 border-t border-slate-100">
            <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                <i class="bi bi-info-circle text-[#696cff]"></i> Penjelasan Status:
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                <div class="flex items-start gap-3 bg-white p-3 rounded-lg border border-slate-200">
                    <span class="w-8 h-8 rounded-full bg-[#e8fadf] text-[#71dd37] flex items-center justify-center text-lg shrink-0"><i class="bi bi-arrow-up-circle"></i></span>
                    <div>
                        <p class="font-bold text-slate-800">Stok Lebih</p>
                        <p class="text-slate-500 text-xs mt-0.5">Stok fisik > stok gudang (kelebihan)</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-white p-3 rounded-lg border border-slate-200">
                    <span class="w-8 h-8 rounded-full bg-[#ffe0db] text-[#ff3e1d] flex items-center justify-center text-lg shrink-0"><i class="bi bi-arrow-down-circle"></i></span>
                    <div>
                        <p class="font-bold text-slate-800">Stok Kurang</p>
                        <p class="text-slate-500 text-xs mt-0.5">Stok fisik &lt; stok gudang (kekurangan)</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-white p-3 rounded-lg border border-slate-200">
                    <span class="w-8 h-8 rounded-full bg-[#d7f5fc] text-[#03c3ec] flex items-center justify-center text-lg shrink-0"><i class="bi bi-check-circle"></i></span>
                    <div>
                        <p class="font-bold text-slate-800">Stok Cocok</p>
                        <p class="text-slate-500 text-xs mt-0.5">Stok fisik = stok gudang</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update selisih when physical stock input changes
document.querySelectorAll('.physical-stock-input').forEach(input => {
    input.addEventListener('input', function() {
        const idx = this.id.replace('physical-', '');
        const qtySystem = parseFloat(this.dataset.qtySystem);
        const qtyPhysical = parseFloat(this.value) || 0;
        const selisih = qtyPhysical - qtySystem;
        
        // Update selisih display
        const row = document.getElementById('row-' + idx);
        const selisihSpan = row.querySelector('.selisih-value');
        selisihSpan.textContent = (selisih >= 0 ? '+' : '') + selisih.toFixed(2);
        
        // Update status color
        let statusClass = 'text-slate-600';
        if (selisih > 0) statusClass = 'text-[#71dd37]';
        else if (selisih < 0) statusClass = 'text-[#ff3e1d]';
        
        selisihSpan.className = 'font-extrabold text-base selisih-value ' + statusClass;
        
        // Update status badge
        const statusBadge = row.querySelector('.status-badge');
        if (selisih > 0) {
            statusBadge.className = 'px-3 py-1.5 bg-[#e8fadf] text-[#71dd37] border border-[#71dd37]/20 rounded-full text-xs font-bold status-badge inline-flex items-center';
            statusBadge.innerHTML = '<i class="bi bi-arrow-up-circle mr-1"></i> Lebih';
        } else if (selisih < 0) {
            statusBadge.className = 'px-3 py-1.5 bg-[#ffe0db] text-[#ff3e1d] border border-[#ff3e1d]/20 rounded-full text-xs font-bold status-badge inline-flex items-center';
            statusBadge.innerHTML = '<i class="bi bi-arrow-down-circle mr-1"></i> Kurang';
        } else {
            statusBadge.className = 'px-3 py-1.5 bg-[#d7f5fc] text-[#03c3ec] border border-[#03c3ec]/20 rounded-full text-xs font-bold status-badge inline-flex items-center';
            statusBadge.innerHTML = '<i class="bi bi-check-circle mr-1"></i> Cocok';
        }
    });
});

// Save physical stock
async function savePhysicalStock(idx) {
    const input = document.getElementById('physical-' + idx);
    const saveBtn = document.getElementById('save-' + idx);
    
    const qtyPhysical = parseFloat(input.value) || 0;
    const itemId = input.dataset.itemId;
    const adjustmentId = input.dataset.adjustmentId;
    const inventoryId = input.dataset.inventoryId;
    const qtySystem = parseFloat(input.dataset.qtySystem);

    if (!itemId) {
        alert('<i class="bi bi-x-lg"></i> ID item tidak ditemukan');
        return;
    }

    // Disable button and show loading state
    saveBtn.disabled = true;
    const originalText = saveBtn.textContent;
    saveBtn.textContent = '⏳ Menyimpan...';
    saveBtn.classList.add('opacity-50', 'cursor-not-allowed');

    try {
        const response = await fetch('{{ route("admin.inventory.stock-opname.save-physical-stock") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                item_id: itemId,
                qty_physical: qtyPhysical,
                qty_system: qtySystem,
                inventory_id: inventoryId,
                adjustment_id: adjustmentId
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Show success notification
            showNotification('<i class="bi bi-check2-square"></i> Stok fisik berhasil disimpan!', 'success', 2000);
            
            // Update input background color
            input.classList.remove('border-yellow-400');
            input.classList.add('border-green-400');
            
            setTimeout(() => {
                input.classList.remove('border-green-400');
                input.classList.add('border-gray-300');
            }, 2000);
        } else {
            showNotification('<i class="bi bi-x-lg"></i> Gagal menyimpan: ' + (data.message || 'Unknown error'), 'error', 3000);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('<i class="bi bi-x-lg"></i> Terjadi kesalahan: ' + error.message, 'error', 3000);
    } finally {
        // Re-enable button
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
        saveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

// Show notification
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white font-medium z-50 ${
        type === 'success' ? 'bg-green-600' : 'bg-red-600'
    }`;
    notification.innerHTML = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, duration);
}
</script>
@endsection
