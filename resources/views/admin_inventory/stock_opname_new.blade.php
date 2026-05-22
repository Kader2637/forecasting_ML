@extends('layouts.admin_inventory.app')

@section('title', 'Perbandingan Stok Fisik & Gudang - Gentle Living')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">⚖️ Perbandingan Stok Fisik & Gudang</h1>
            <p class="text-lg text-gray-600">{{ $comparisonStats['branch_name'] }} - Total {{ $comparisonStats['total_items_checked'] }} Item</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Items</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $comparisonStats['total_items_checked'] }}</p>
                    </div>
                    <div class="text-4xl">📊</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Stok Lebih</p>
                        <p class="text-3xl font-bold text-green-600">{{ $comparisonStats['items_with_surplus'] }}</p>
                    </div>
                    <div class="text-4xl">📈</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Stok Kurang</p>
                        <p class="text-3xl font-bold text-red-600">{{ $comparisonStats['items_with_deficit'] }}</p>
                    </div>
                    <div class="text-4xl">📉</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Stok Cocok</p>
                        <p class="text-3xl font-bold text-purple-600">{{ $comparisonStats['items_matched'] }}</p>
                    </div>
                    <div class="text-4xl">✓</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Selisih</p>
                        <p class="text-3xl font-bold {{ $comparisonStats['total_difference'] > 0 ? 'text-green-600' : ($comparisonStats['total_difference'] < 0 ? 'text-red-600' : 'text-gray-600') }}">
                            {{ $comparisonStats['total_difference'] >= 0 ? '+' : '' }}{{ number_format($comparisonStats['total_difference'], 0) }}
                        </p>
                    </div>
                    <div class="text-4xl">∑</div>
                </div>
            </div>
        </div>

        <!-- Stock Comparison Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <!-- Instructions -->
            <div class="bg-blue-50 border-b border-blue-200 px-6 py-4">
                <p class="text-sm text-blue-900">
                    💡 <strong>Instruksi:</strong> Masukkan stok fisik hasil penghitungan di kolom "Stok Fisik" dan klik tombol simpan untuk menyimpan perubahan.
                </p>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">No</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Kode Item</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Nama Item</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Stok Gudang</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Stok Fisik (Edit)</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Selisih</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold">Status</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="stockTable">
                        @forelse($stockComparison as $idx => $item)
                        @php
                            $status = '';
                            $statusColor = '';
                            $statusBg = '';
                            
                            if ($item->qty_difference > 0) {
                                $status = '📈 Lebih';
                                $statusColor = 'text-green-800';
                                $statusBg = 'bg-green-100';
                            } elseif ($item->qty_difference < 0) {
                                $status = '📉 Kurang';
                                $statusColor = 'text-red-800';
                                $statusBg = 'bg-red-100';
                            } else {
                                $status = '✓ Cocok';
                                $statusColor = 'text-purple-800';
                                $statusBg = 'bg-purple-100';
                            }
                        @endphp
                        <tr class="hover:bg-indigo-50 transition-colors" id="row-{{ $idx }}">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ $idx + 1 }}</td>
                            <td class="px-6 py-4 text-sm">
                                <code class="bg-gray-100 px-2 py-1 rounded text-gray-700">{{ $item->item_code }}</code>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-900">{{ $item->item_name }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                    {{ number_format($item->qty_system, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <input 
                                    type="number" 
                                    step="0.01"
                                    class="physical-stock-input border border-gray-300 rounded px-3 py-2 text-sm font-medium text-right w-24 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    value="{{ number_format($item->qty_physical, 2, '.', '') }}"
                                    data-item-id="{{ $item->item_id }}"
                                    data-adjustment-id="{{ $item->adjustment_id }}"
                                    data-inventory-id="{{ $item->inventory_id }}"
                                    data-qty-system="{{ $item->qty_system }}"
                                    id="physical-{{ $idx }}"
                                />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-semibold text-lg selisih-value {{ $item->qty_difference > 0 ? 'text-green-600' : ($item->qty_difference < 0 ? 'text-red-600' : 'text-gray-600') }}" data-original="{{ $item->qty_difference }}">
                                    {{ $item->qty_difference > 0 ? '+' : '' }}{{ number_format($item->qty_difference, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 {{ $statusBg }} {{ $statusColor }} rounded-full text-sm font-medium status-badge">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button 
                                    onclick="savePhysicalStock({{ $idx }})"
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-medium save-btn"
                                    id="save-{{ $idx }}"
                                >
                                    ✓ Simpan
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                Tidak ada data stok untuk ditampilkan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Legend -->
            <div class="bg-gradient-to-r from-blue-50 to-cyan-50 px-6 py-4 border-t border-blue-200">
                <p class="text-sm font-semibold text-gray-700 mb-3">📖 Penjelasan Status:</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="flex items-start gap-2">
                        <span class="text-lg">📈</span>
                        <div>
                            <p class="font-semibold text-green-700">Stok Lebih</p>
                            <p class="text-gray-600">Stok fisik > stok gudang (kelebihan)</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-lg">📉</span>
                        <div>
                            <p class="font-semibold text-red-700">Stok Kurang</p>
                            <p class="text-gray-600">Stok fisik < stok gudang (kekurangan)</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-lg">✓</span>
                        <div>
                            <p class="font-semibold text-purple-700">Stok Cocok</p>
                            <p class="text-gray-600">Stok fisik = stok gudang</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update selisih when physical stock input changes
document.querySelectorAll('.physical-stock-input').forEach(input => {
    input.addEventListener('change', function() {
        const idx = this.id.replace('physical-', '');
        const qtySystem = parseFloat(this.dataset.qtySystem);
        const qtyPhysical = parseFloat(this.value) || 0;
        const selisih = qtyPhysical - qtySystem;
        
        // Update selisih display
        const row = document.getElementById('row-' + idx);
        const selisihSpan = row.querySelector('.selisih-value');
        selisihSpan.textContent = (selisih >= 0 ? '+' : '') + selisih.toFixed(2);
        
        // Update status color
        let statusClass = 'text-gray-600';
        if (selisih > 0) statusClass = 'text-green-600';
        else if (selisih < 0) statusClass = 'text-red-600';
        
        selisihSpan.className = 'font-semibold text-lg selisih-value ' + statusClass;
        
        // Update status badge
        const statusBadge = row.querySelector('.status-badge');
        if (selisih > 0) {
            statusBadge.className = 'px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium status-badge';
            statusBadge.textContent = '📈 Lebih';
        } else if (selisih < 0) {
            statusBadge.className = 'px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium status-badge';
            statusBadge.textContent = '📉 Kurang';
        } else {
            statusBadge.className = 'px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium status-badge';
            statusBadge.textContent = '✓ Cocok';
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
