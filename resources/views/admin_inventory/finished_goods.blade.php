@extends('layouts.admin_inventory.app')

@section('title', 'Buffer Stock Produk')

@include('components.buffer_stock_info')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2"> Buffer Stock Produk </h1>
            <p class="text-lg text-gray-600">Daftar produk jadi dan monitoring buffer stock untuk manajemen inventori yang optimal</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Data</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $summary['total'] }}</p>
                    </div>
                    <div class="text-4xl"><i class="bi bi-box-seam"></i></div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Harus Order</p>
                        <p class="text-3xl font-bold text-red-600">{{ $summary['needs_order'] }}</p>
                    </div>
                    <div class="text-4xl"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Mencukupi</p>
                        <p class="text-3xl font-bold text-green-600">{{ $summary['sufficient'] }}</p>
                    </div>
                    <div class="text-4xl"><i class="bi bi-check2-square"></i></div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <form method="GET" action="{{ route('admin.inventory.finished-goods') }}" class="flex-1 flex gap-2">
                    <select
                        name="per_page"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        @foreach([10, 15, 25, 50, 100] as $option)
                            <option value="{{ $option }}" {{ (int) $perPage === $option ? 'selected' : '' }}>
                                {{ $option }}/hal
                            </option>
                        @endforeach
                    </select>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari produk jadi..."
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Cari
                    </button>
                </form>
                <button 
                    type="button" 
                    id="updateRopBtn" 
                    onclick="updateBufferStockFromRop()"
                    class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2 whitespace-nowrap"
                    title="Update Buffer Stock dari file CSV ROP"
                >
                    <i class="bi bi-cloud-arrow-down"></i>
                    <span id="updateRopText">Update ROP</span>
                    <span id="updateRopSpinner" class="hidden animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">No.</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Nama Satuan</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Kategori</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Stock</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Buffer Stock</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Stock difference</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Lead Time</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($rawMaterials as $row)
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="px-6 py-4 align-top text-sm text-gray-700">{{ $rawMaterials->firstItem() + $loop->index }}.</td>
                                <td class="px-6 py-4 align-top">
                                    <div class="font-semibold text-gray-900">{{ $row['name_item'] }}</div>
                                    @if(!empty($row['code_item']))
                                        <div class="text-sm text-gray-500 mt-1">Kode: {{ $row['code_item'] }}</div>
                                    @endif
                                    <div class="text-xs text-gray-500 mt-1">Inventori: {{ $row['inventory'] }}</div>
                                </td>
                                <td class="px-6 py-4 align-top text-sm text-gray-700">{{ $row['category'] }}</td>
                                <td class="px-6 py-4 align-top text-right text-sm font-semibold text-gray-900">{{ number_format($row['stock']) }}</td>
                                <td class="px-6 py-4 align-top text-right text-sm text-gray-700 buffer-stock-cell" data-product-name="{{ $row['name_item'] }}">{{ number_format($row['buffer_stock']) }}</td>
                                <td class="px-6 py-4 align-top text-right">
                                    <span class="px-3 py-1 rounded-full text-sm font-medium text-white {{ $row['needs_order'] ? 'bg-red-600 text-red-800' : 'bg-green-600 text-green-800' }}">
                                        {{ $row['needs_order'] ? ' ' . abs($row['stock_difference']) : ' ' . $row['stock_difference'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top text-right text-sm font-semibold text-gray-900">7 hari</td>
                                <td class="px-6 py-4 align-top text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <button type="button" onclick="openDetailModal({{ $row['item_stock_id'] }})" class="text-blue-600 hover:text-blue-900 text-sm font-medium" title="Lihat data produk jadi">
                                            Detail
                                        </button>
                                        <button type="button" onclick="openEditModal({{ $row['item_stock_id'] }})" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium" title="Edit data produk jadi">
                                            Edit
                                        </button>
                                        <button type="button" onclick="deleteFinishedGoods({{ $row['item_stock_id'] }}, @js($row['name_item']))" class="text-rose-600 hover:text-rose-900 text-sm font-medium" title="Hapus data produk jadi">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    Data produk jadi tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white px-6 py-4 border-t border-gray-200">
                {{ $rawMaterials->links() }}
            </div>
        </div>

        <div class="mt-8 bg-blue-50 rounded-lg p-6 border border-blue-200">
            <h3 class="text-lg font-semibold text-blue-900 mb-4"> Penjelasan Buffer Stock</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <h4 class="font-semibold text-blue-800 mb-2"> Keterangan:</h4>
                    <ul class="space-y-2 text-blue-700">
                        <li><strong>Buffer Stock:</strong> Stok pengaman untuk mengantisipasi kebutuhan</li>
                        <li><strong>Stock Difference:</strong> Selisih stok terhadap buffer stock</li>
                        <li><strong>Harus Order:</strong> Stok di bawah buffer stock</li>
                        <li><strong>Mencukupi:</strong> Stok masih aman</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-blue-800 mb-2"> Interpretasi Status:</h4>
                    <ul class="space-y-2 text-blue-700">
                        <li> <strong>Harus order:</strong> Stok perlu ditambah segera</li>
                        <li> <strong>Mencukupi:</strong> Stok masih aman untuk operasional</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full max-h-screen overflow-y-auto">
        <div class="bg-[#d3ebf4] border-b border-[#b9dbe8] px-6 py-4 sticky top-0">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-slate-800">Detail Produk Jadi</h2>
                <button type="button" onclick="closeDetailModal()" class="text-slate-500 hover:text-slate-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6 text-slate-800" id="detailContent">
            <div class="flex justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex gap-3 justify-end sticky bottom-0">
            <button type="button" onclick="closeDetailModal()" class="px-4 py-2 rounded border border-gray-300 text-slate-700 hover:bg-gray-50">
                Tutup
            </button>
            <button type="button" id="editFromDetailBtn"
             class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                Edit Stok
            </button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full max-h-screen overflow-y-auto">
        <div class="bg-[#d3ebf4] border-b border-[#b9dbe8] px-6 py-4 sticky top-0">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-slate-800">Edit Stok Produk Jadi</h2>
                <button type="button" onclick="closeEditModal()" class="text-slate-500 hover:text-slate-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="editForm" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            
            <div id="editContentInfo" class="pb-4 border-b">
                <p class="text-sm text-slate-500">Nama Item</p>
                <p id="editItemName" class="text-lg font-semibold text-slate-800">-</p>
                <p class="text-sm text-slate-500 mt-3">Kode Item</p>
                <p id="editItemCode" class="text-sm text-slate-700">-</p>
                <p class="text-sm text-slate-500 mt-3">Buffer Stock</p>
                <p id="editBufferStock" class="text-sm text-slate-700">-</p>
            </div>

            <div>
                <label for="editStock" class="block text-sm font-medium text-slate-700 mb-1">Stock Baru</label>
                <input
                    id="editStock"
                    type="number"
                    name="stock"
                    min="0"
                    max="9999999"
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-200"
                    required
                >
                <p id="editError" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>

            <div class="bg-gray-50 -mx-6 -mb-6 px-6 py-4 border-t border-gray-200 flex gap-3 justify-end sticky bottom-0">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded border border-gray-300 text-slate-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 flex items-center gap-2">
                    <span id="submitText">Simpan</span>
                    <span id="submitSpinner" class="hidden animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentItemStockId = null;
const deleteFinishedGoodsUrlTemplate = '{{ route("admin.inventory.finished-goods.destroy", ["itemStock" => "__ID__"]) }}';

function getDeleteFinishedGoodsUrl(itemStockId) {
    return deleteFinishedGoodsUrlTemplate.replace('__ID__', encodeURIComponent(itemStockId));
}

async function deleteFinishedGoods(itemStockId, itemName) {
    if (!itemStockId) {
        alert('ID produk jadi tidak valid.');
        return;
    }

    const confirmDelete = confirm(`Hapus data produk jadi "${itemName}"? Tindakan ini tidak dapat dibatalkan.`);
    if (!confirmDelete) {
        return;
    }

    try {
        const response = await fetch(getDeleteFinishedGoodsUrl(itemStockId), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                    document.querySelector('input[name="_token"]')?.value
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showNotification(`<i class="bi bi-check-lg"></i> ${data.message}`, 'success');
            setTimeout(() => location.reload(), 1500);
            return;
        }

        showNotification(`<i class="bi bi-x-lg"></i> ${data.message || 'Gagal menghapus data produk jadi.'}`, 'error');
    } catch (error) {
        console.error('Error:', error);
        showNotification(`<i class="bi bi-x-lg"></i> Terjadi kesalahan saat menghapus data.`, 'error');
    }
}

async function openDetailModal(itemStockId) {
    currentItemStockId = itemStockId;
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');
    
    modal.classList.remove('hidden');
    content.innerHTML = '<div class="flex justify-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div></div>';

    try {
        const response = await fetch(`/admin/inventory/finished-goods/${itemStockId}`);
        const data = await response.json();

        content.innerHTML = `
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-slate-500">Nama Item</p>
                    <p class="text-lg font-semibold">${data.name_item || '-'}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Kode Item</p>
                    <p class="text-sm text-slate-700">${data.code_item || '-'}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Kategori</p>
                    <p class="text-sm text-slate-700">${data.category || 'Tanpa Kategori'}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Inventori</p>
                    <p class="text-sm text-slate-700">${data.inventory || '-'}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Stok Saat Ini</p>
                    <p class="text-lg font-semibold">${parseInt(data.stock).toLocaleString('id-ID')}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Buffer Stock</p>
                    <p class="text-lg font-semibold">${parseInt(data.buffer_stock).toLocaleString('id-ID')}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Stock Difference</p>
                    <span class="inline-block px-3 py-1 text-white rounded ${data.stock_difference > 0 ? 'bg-red-500' : 'bg-emerald-500'}">
                        ${Math.abs(data.stock_difference).toLocaleString('id-ID')}
                    </span>
                </div>
            </div>
        `;

        document.getElementById('editFromDetailBtn').onclick = function() {
            closeDetailModal();
            openEditModal(itemStockId);
        };
    } catch (error) {
        console.error('Error:', error);
        content.innerHTML = '<p class="text-red-600 text-center">Gagal memuat data</p>';
    }
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

async function openEditModal(itemStockId) {
    currentItemStockId = itemStockId;
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');
    
    modal.classList.remove('hidden');
    document.getElementById('editError').classList.add('hidden');

    try {
        const response = await fetch(`/admin/inventory/finished-goods/${itemStockId}`);
        const data = await response.json();

        document.getElementById('editItemName').textContent = data.name_item || '-';
        document.getElementById('editItemCode').textContent = data.code_item ? `Kode: ${data.code_item}` : '-';
        document.getElementById('editBufferStock').textContent = parseInt(data.buffer_stock).toLocaleString('id-ID');
        document.getElementById('editStock').value = data.stock;

        form.action = `/admin/inventory/finished-goods/${itemStockId}`;
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('editError').textContent = 'Gagal memuat data';
        document.getElementById('editError').classList.remove('hidden');
    }
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const stock = document.getElementById('editStock').value;
    const submitBtn = this.querySelector('button[type="submit"]');
    const submitText = document.getElementById('submitText');
    const submitSpinner = document.getElementById('submitSpinner');

    submitText.classList.add('hidden');
    submitSpinner.classList.remove('hidden');
    submitBtn.disabled = true;

    try {
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || 
                                document.querySelector('input[name="_token"]')?.value
            },
            body: JSON.stringify({
                _method: 'PUT',
                stock: stock
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            closeEditModal();
            location.reload();
        } else {
            document.getElementById('editError').textContent = data.message || 'Gagal menyimpan data';
            document.getElementById('editError').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('editError').textContent = 'Terjadi kesalahan saat menyimpan';
        document.getElementById('editError').classList.remove('hidden');
    } finally {
        submitText.classList.remove('hidden');
        submitSpinner.classList.add('hidden');
        submitBtn.disabled = false;
    }
});

/**
 * Update Buffer Stock dari CSV ROP values
 */
async function updateBufferStockFromRop() {
    const btn = document.getElementById('updateRopBtn');
    const text = document.getElementById('updateRopText');
    const spinner = document.getElementById('updateRopSpinner');
    
    // Disable button and show spinner
    btn.disabled = true;
    text.classList.add('hidden');
    spinner.classList.remove('hidden');

    try {
        const response = await fetch('{{ route("admin.inventory.finished-goods.update-rop") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || 
                                document.querySelector('input[name="_token"]')?.value
            },
            body: JSON.stringify({
                inventory_id: 1
            })
        });

        const data = await response.json();

        if (data.success) {
            // Show success notification
            const summary = data.data;
            const message = `
                <div class="text-left">
                    <strong>${data.message}</strong><br/>
                    <small class="text-gray-600">
                        Updated: ${summary.updated} | 
                        Skipped: ${summary.skipped} | 
                        Errors: ${summary.errors}
                    </small>
                </div>
            `;
            showNotification(`<i class="bi bi-check-lg"></i> ${message}`, 'success', 5000);
            
            // Reload page after 2 seconds
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification(`<i class="bi bi-x-lg"></i> ${data.message}`, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(`<i class="bi bi-x-lg"></i> Terjadi kesalahan: ${error.message}`, 'error');
    } finally {
        // Re-enable button
        btn.disabled = false;
        text.classList.remove('hidden');
        spinner.classList.add('hidden');
    }
}


</script>
@endsection
