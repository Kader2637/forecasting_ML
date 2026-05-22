@extends('layouts.admin_inventory.app')

@section('title', 'Buffer Stock - Bahan Baku')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Buffer Stock - Bahan Baku</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-600">Total Bahan</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($summary['total_materials'] ?? 0, 0) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-600">Total Nilai Inventory</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">Rp {{ number_format($summary['total_inventory_value'] ?? 0, 0) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-600">Rata-rata Buffer Stock</p>
                <p class="text-3xl font-bold text-indigo-700 mt-2">{{ number_format($summary['avg_buffer_stock'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-600">Rata-rata Lead Time (hari)</p>
                <p class="text-3xl font-bold text-orange-700 mt-2">{{ number_format($summary['avg_lead_time'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-600">Stok Kosong / Di bawah Buffer</p>
                <p class="text-3xl font-bold text-red-700 mt-2">
                    {{ number_format($summary['empty_stock'] ?? 0, 0) }} / {{ number_format($summary['items_below_buffer'] ?? 0, 0) }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <form method="GET" class="flex-1 flex gap-2">
                    <input
                        type="text"
                        name="search"
                        placeholder="Cari ID, nama bahan, unit, supplier..."
                        value="{{ $search }}"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Cari
                    </button>
                </form>

                <div class="flex gap-2 flex-wrap">

                    <button onclick="openProductionModal()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2 text-sm">
                        <span><i class="bi bi-tools"></i></span> Produksi
                    </button>

                    <a href="{{ route('admin.inventory.buffer-stock.export-excel') }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center gap-2 text-sm font-semibold">
                        <span><i class="bi bi-download"></i></span> Export Excel
                    </a>

                    <a href="{{ route('admin.inventory.buffer-stock.download-template') }}" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 flex items-center gap-2 text-sm font-semibold">
                        <span><i class="bi bi-file-earmark-spreadsheet"></i></span> Template
                    </a>

                    <button onclick="openImportModal()" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 flex items-center gap-2 text-sm font-semibold">
                        <span><i class="bi bi-upload"></i></span> Import Excel
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-right text-sm font-semibold">ID</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Nama Bahan</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Unit</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Harga Beli</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Stok Saat Ini</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Lead Time (hari)</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Buffer Stock</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Supplier</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($materialData as $material)
                        @php
                            $itemRawId = (int) ($material['item_raw_id'] ?? 0);
                            $materialPayload = [
                                'id' => $itemRawId,
                                'material_name' => (string) ($material['material_name'] ?? ''),
                                'unit' => (string) ($material['unit'] ?? ''),
                                'purchase_price' => (float) ($material['purchase_price'] ?? 0),
                                'current_stock' => (float) ($material['current_stock'] ?? 0),
                                'lead_time_days' => (int) ($material['lead_time_days'] ?? 0),
                                'buffer_stock' => (float) ($material['buffer_stock'] ?? 0),
                                'supplier_name' => (string) ($material['supplier_name'] ?? ''),
                            ];
                        @endphp
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-900">{{ $material['material_name'] ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $material['unit'] ?? '-' }}</td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900">Rp {{ number_format($material['purchase_price'] ?? 0, 0) }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ number_format($material['current_stock'] ?? 0, 0) }}</td>
                            <td class="px-6 py-4 text-right text-sm text-gray-700">{{ number_format($material['lead_time_days'] ?? 0, 0) }}</td>
                            <td class="px-6 py-4 text-right text-sm text-gray-700">{{ number_format($material['buffer_stock'] ?? 0, 0) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $material['supplier_name'] ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        type="button"
                                        onclick="viewMaterialDetail({{ $itemRawId }})"
                                        class="px-2.5 py-1.5 bg-sky-100 text-sky-800 rounded-lg text-xs font-semibold hover:bg-sky-200"
                                        {{ $itemRawId <= 0 ? 'disabled' : '' }}
                                    >
                                        Lihat
                                    </button>
                                    <button
                                        type="button"
                                        onclick='openEditModal(@json($materialPayload))'
                                        class="px-2.5 py-1.5 bg-amber-100 text-amber-800 rounded-lg text-xs font-semibold hover:bg-amber-200"
                                        {{ $itemRawId <= 0 ? 'disabled' : '' }}
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        onclick="deleteMaterial({{ $itemRawId }}, @js($material['material_name'] ?? '-'))"
                                        class="px-2.5 py-1.5 bg-rose-100 text-rose-800 rounded-lg text-xs font-semibold hover:bg-rose-200"
                                        {{ $itemRawId <= 0 ? 'disabled' : '' }}
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                Tidak ada data bahan baku. Gunakan fitur Import Excel untuk menambahkan bahan baku.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white px-6 py-4 border-t border-gray-200">
                {{ $materialData->links() }}
            </div>
        </div>


    </div>
</div>

<div id="customAlertModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden">
        <div id="alertHeader" class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <span id="alertIcon" class="text-2xl"></span>
            <h3 id="alertTitle" class="text-lg font-semibold text-gray-900">Alert</h3>
        </div>
        <div id="alertContent" class="px-6 py-4 text-gray-700">
            Pesan alert akan ditampilkan di sini
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
            <button type="button" id="alertConfirmBtn" onclick="closeCustomAlert()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                OK
            </button>
            <button type="button" id="alertCancelBtn" onclick="closeCustomAlert(false)" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium" style="display: none;">
                Batal
            </button>
        </div>
    </div>
</div>

<script>
let alertCallback = null;

function showCustomAlert(title, message, type = 'info') {
    const modal = document.getElementById('customAlertModal');
    const titleEl = document.getElementById('alertTitle');
    const contentEl = document.getElementById('alertContent');
    const headerEl = document.getElementById('alertHeader');
    const iconEl = document.getElementById('alertIcon');
    const confirmBtn = document.getElementById('alertConfirmBtn');
    const cancelBtn = document.getElementById('alertCancelBtn');

    titleEl.textContent = title;
    contentEl.innerHTML = message;
    cancelBtn.style.display = 'none';

    // Set styling based on type
    headerEl.className = 'px-6 py-4 border-b border-gray-200 flex items-center gap-3';
    if (type === 'success') {
        headerEl.classList.add('bg-emerald-50');
        iconEl.innerHTML = '<i class="bi bi-check2-square"></i>';
        confirmBtn.className = 'px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium';
    } else if (type === 'error') {
        headerEl.classList.add('bg-red-50');
        iconEl.innerHTML = '<i class="bi bi-x-circle"></i>';
        confirmBtn.className = 'px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium';
    } else if (type === 'warning') {
        headerEl.classList.add('bg-amber-50');
        iconEl.innerHTML = '<i class="bi bi-exclamation-triangle"></i>';
        confirmBtn.className = 'px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-medium';
    } else {
        headerEl.classList.add('bg-blue-50');
        iconEl.innerHTML = '<i class="bi bi-info-circle"></i>';
        confirmBtn.className = 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function showConfirm(message, callback) {
    const modal = document.getElementById('customAlertModal');
    const titleEl = document.getElementById('alertTitle');
    const contentEl = document.getElementById('alertContent');
    const headerEl = document.getElementById('alertHeader');
    const iconEl = document.getElementById('alertIcon');
    const confirmBtn = document.getElementById('alertConfirmBtn');
    const cancelBtn = document.getElementById('alertCancelBtn');

    titleEl.textContent = 'Konfirmasi';
    contentEl.innerHTML = message;
    
    headerEl.className = 'px-6 py-4 border-b border-gray-200 flex items-center gap-3 bg-blue-50';
    iconEl.innerHTML = '<i class="bi bi-question-circle"></i>';
    confirmBtn.textContent = 'Ya, Lanjutkan';
    confirmBtn.className = 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium';
    cancelBtn.textContent = 'Batal';
    cancelBtn.className = 'px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium';
    cancelBtn.style.display = 'block';

    alertCallback = callback;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCustomAlert(confirmed = true) {
    const modal = document.getElementById('customAlertModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    
    if (alertCallback) {
        alertCallback(confirmed);
        alertCallback = null;
    }
}

// Keyboard support
document.getElementById('customAlertModal').addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCustomAlert(false);
    } else if (e.key === 'Enter') {
        closeCustomAlert(true);
    }
});

const detailUrlTemplate = '{{ route("admin.inventory.buffer-stock.detail", ["itemRawId" => "__ID__"]) }}';
const updateUrlTemplate = '{{ route("admin.inventory.buffer-stock.update", ["itemRawId" => "__ID__"]) }}';
const deleteUrlTemplate = '{{ route("admin.inventory.buffer-stock.destroy", ["itemRawId" => "__ID__"]) }}';
const productionMasterDataUrl = '{{ route("admin.inventory.buffer-stock.production-master-data") }}';
const productionExecuteUrl = '{{ route("admin.inventory.buffer-stock.produce") }}';

function buildUrl(template, id) {
    return template.replace('__ID__', encodeURIComponent(id));
}

function showModal(id) {
    const modal = document.getElementById(id);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function closeDetailModal() {
    hideModal('detailModal');
}

function closeEditModal() {
    hideModal('editModal');
}

let productionProducts = [];

function closeProductionModal() {
    hideModal('productionModal');
}

function renderProductionPreview() {
    const productSelect = document.getElementById('production_item_id');
    const qtyInput = document.getElementById('production_qty');
    const preview = document.getElementById('productionPreview');
    const help = document.getElementById('production_product_help');
    const recipeContainer = document.getElementById('productionRecipeContainer');

    const selectedItemId = parseInt(productSelect.value || '0', 10);
    const selectedProduct = productionProducts.find((p) => parseInt(p.item_id, 10) === selectedItemId);

    if (!selectedProduct) {
        qtyInput.removeAttribute('max');
        preview.innerHTML = 'Pilih produk untuk melihat estimasi maksimum produksi berdasarkan resep.';
        help.innerText = 'Pilih produk jadi, sistem akan menampilkan kebutuhan bahan otomatis.';
        recipeContainer.innerHTML = '';
        return;
    }

    const max = parseInt(selectedProduct.max_producible || 0, 10);
    const qtyRequested = Math.max(1, parseInt(qtyInput.value || '1', 10));

    if (max > 0) {
        qtyInput.max = String(max);
    } else {
        qtyInput.removeAttribute('max');
    }
    help.innerText = `Maksimum produksi saat ini: ${max}`;

    const bomLines = (selectedProduct.bom_preview || []).map((bom) => {
        const requiredPerUnit = Number(bom.required_per_unit || 0);
        const stockNow = Number(bom.stock_now || 0);
        const requiredForQty = requiredPerUnit * qtyRequested;

        return `<li>${bom.material_name}: perlu ${requiredPerUnit} ${bom.unit || ''}/unit, kebutuhan qty ${qtyRequested} = <strong>${requiredForQty.toFixed(1)}</strong> (stok ${stockNow})</li>`;
    }).join('');

    const shortageRows = (selectedProduct.bom_preview || [])
        .map((bom) => {
            const requiredPerUnit = Number(bom.required_per_unit || 0);
            const stockNow = Number(bom.stock_now || 0);
            const requiredForQty = requiredPerUnit * qtyRequested;
            const shortage = requiredForQty - stockNow;

            if (shortage <= 0) {
                return null;
            }

            return {
                material_name: bom.material_name || '-',
                unit: bom.unit || '',
                required: requiredForQty,
                stock: stockNow,
                shortage,
            };
        })
        .filter(Boolean);

    const shortageHtml = shortageRows.length > 0
        ? `
            <div class="mt-3 p-3 rounded-lg bg-rose-50 border border-rose-200">
                <p class="text-rose-700 font-semibold mb-1">Bahan Kurang untuk qty ${qtyRequested}:</p>
                <ul class="list-disc pl-5 text-rose-700 space-y-1">
                    ${shortageRows.map((row) => `<li>${row.material_name}: butuh ${row.required.toFixed(1)} ${row.unit}, stok ${row.stock.toFixed(1)} ${row.unit}, kurang ${row.shortage.toFixed(1)} ${row.unit}</li>`).join('')}
                </ul>
            </div>
        `
        : '';

    preview.innerHTML = `
        <div class="space-y-2">
            <p><strong>${selectedProduct.name_item || '-'}</strong> | Maksimum: <strong>${max}</strong></p>
            <ul class="list-disc pl-5 space-y-1">${bomLines || '<li>Tidak ada rincian BOM.</li>'}</ul>
            ${shortageHtml}
        </div>
    `;

    if (!qtyInput.value || parseInt(qtyInput.value, 10) > max) {
        qtyInput.value = max > 0 ? Math.min(max, 1) : '';
    }
}

function openProductionModal() {
    document.getElementById('production_item_id').innerHTML = '<option value="">Memuat produk...</option>';
    document.getElementById('production_inventory_id').innerHTML = '<option value="">Memuat inventori...</option>';
    document.getElementById('production_qty').removeAttribute('max');
    document.getElementById('production_qty').value = '';
    document.getElementById('production_notes').value = '';
    document.getElementById('productionPreview').innerHTML = 'Memuat data BOM...';
    productionProducts = [];

    showModal('productionModal');

    fetch(productionMasterDataUrl)
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Gagal memuat opsi produksi.');
            }
            return data;
        })
        .then((data) => {
            const productSelect = document.getElementById('production_item_id');
            const inventorySelect = document.getElementById('production_inventory_id');

            productionProducts = data.products || [];
            console.log('Production products loaded:', productionProducts.length, 'items');

            productSelect.innerHTML = '<option value="">Pilih produk jadi...</option>';
            productionProducts.forEach((p) => {
                const option = document.createElement('option');
                option.value = p.item_id;
                option.textContent = `${p.code_item || ''} - ${p.name_item || '-'} (maks ${p.max_producible || 0})`;
                productSelect.appendChild(option);
            });

            inventorySelect.innerHTML = '<option value="">Pilih inventori...</option>';
            (data.inventories || []).forEach((inv) => {
                const option = document.createElement('option');
                option.value = inv.inventory_id;
                option.textContent = `${inv.name_inventory || 'Inventori'} (Branch ${inv.branch_id || '-'})`;
                inventorySelect.appendChild(option);
            });

            if (productionProducts.length === 0) {
                document.getElementById('productionPreview').innerHTML = '<p class="text-rose-600">Belum ada produk jadi yang memiliki data BOM.</p>';
            } else {
                document.getElementById('productionPreview').innerHTML = 'Pilih produk jadi di atas untuk melihat resep...';
                
                // Attach event listeners SETELAH data dimuat
                setTimeout(() => attachProductionEventListeners(), 100);
            }
        })
        .catch((err) => {
            console.error('Error loading production data:', err);
            document.getElementById('productionPreview').innerHTML = `<p class="text-rose-600">${err.message}</p>`;
            document.getElementById('production_item_id').innerHTML = '<option value="">Tidak tersedia</option>';
            document.getElementById('production_inventory_id').innerHTML = '<option value="">Tidak tersedia</option>';
        });
}

function attachProductionEventListeners() {
    const productSelect = document.getElementById('production_item_id');
    const qtyInput = document.getElementById('production_qty');
    
    if (!productSelect || !qtyInput) return;
    
    // Clone elements to remove old listeners
    const newProductSelect = productSelect.cloneNode(true);
    const newQtyInput = qtyInput.cloneNode(true);
    
    productSelect.parentNode.replaceChild(newProductSelect, productSelect);
    qtyInput.parentNode.replaceChild(newQtyInput, qtyInput);
    
    // Attach fresh event listeners
    document.getElementById('production_item_id').addEventListener('change', function(e) {
        console.log('Produk dipilih:', e.target.value);
        renderProductionPreview();
    });
    
    document.getElementById('production_qty').addEventListener('input', function(e) {
        console.log('Qty diubah:', e.target.value);
        renderProductionPreview();
    });
}

function viewMaterialDetail(itemRawId) {
    if (!itemRawId) {
        showCustomAlert('Error', 'ID bahan baku tidak valid.', 'error');
        return;
    }

    showModal('detailModal');
    const content = document.getElementById('detailModalContent');
    content.innerHTML = 'Memuat data...';

    fetch(buildUrl(detailUrlTemplate, itemRawId))
        .then((res) => {
            if (!res.ok) {
                throw new Error('Detail belum tersedia di database. Silakan sinkronisasi CSV terlebih dahulu.');
            }
            return res.json();
        })
        .then((data) => {
            const material = data.material || {};
            const calculation = data.calculation || {};

            content.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div><span class="text-gray-500">ID:</span> <strong>${material.item_raw_id ?? '-'}</strong></div>
                    <div><span class="text-gray-500">Nama:</span> <strong>${material.material_name ?? '-'}</strong></div>
                    <div><span class="text-gray-500">Stok Saat Ini:</span> <strong>${material.current_stock ?? 0}</strong></div>
                    <div><span class="text-gray-500">Buffer Stock:</span> <strong>${material.buffer_stock ?? 0}</strong></div>
                    <div><span class="text-gray-500">Lead Time:</span> <strong>${material.lead_time_days ?? 0} hari</strong></div>
                    <div><span class="text-gray-500">Reorder Point:</span> <strong>${calculation.reorder_point ?? '-'}</strong></div>
                </div>
            `;
        })
        .catch((err) => {
            content.innerHTML = `<p class="text-rose-600">${err.message}</p>`;
        });
}

function openEditModal(material) {
    if (!material || !material.id) {
        showCustomAlert('Error', 'ID bahan baku tidak valid.', 'error');
        return;
    }

    document.getElementById('edit_item_raw_id').value = material.id;
    document.getElementById('edit_material_name').value = material.material_name ?? '';
    document.getElementById('edit_unit').value = material.unit ?? '';
    document.getElementById('edit_purchase_price').value = material.purchase_price ?? 0;
    document.getElementById('edit_current_stock').value = material.current_stock ?? 0;
    document.getElementById('edit_lead_time_days').value = material.lead_time_days ?? 0;
    document.getElementById('edit_buffer_stock').value = material.buffer_stock ?? 0;
    document.getElementById('edit_supplier_name').value = material.supplier_name ?? '';

    showModal('editModal');
}

function deleteMaterial(itemRawId, materialName) {
    if (!itemRawId) {
        showCustomAlert('Error', 'ID bahan baku tidak valid.', 'error');
        return;
    }

    showConfirm(`Hapus bahan baku "${materialName}"? Tindakan ini tidak dapat dibatalkan.`, function(confirmed) {
        if (!confirmed) return;

        // Get CSRF token from meta tag or input
        let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            csrfToken = document.querySelector('input[name="_token"]')?.value;
        }

        if (!csrfToken) {
            console.error('CSRF token not found');
            showCustomAlert('Error', 'Token keamanan tidak ditemukan. Silakan refresh halaman.', 'error');
            return;
        }

        console.log('Deleting material:', {itemRawId, materialName, hasToken: !!csrfToken});

        fetch(buildUrl(deleteUrlTemplate, itemRawId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(async (res) => {
            console.log('Delete response status:', res.status, res.statusText);
            
            // Get content type
            const contentType = res.headers.get('content-type') || '';
            console.log('Response content-type:', contentType);

            // Check if response has content
            const responseText = await res.clone().text();
            console.log('Response body (first 200 chars):', responseText.substring(0, 200));

            // Try to parse JSON only if it looks like JSON
            let data = null;
            if (contentType.includes('application/json')) {
                try {
                    data = await res.json();
                } catch (parseError) {
                    console.error('Failed to parse JSON:', parseError);
                    throw new Error(`Server returned invalid JSON response: ${responseText.substring(0, 100)}`);
                }
            } else if (responseText.trim()) {
                // If response is not JSON but has content
                console.error('Non-JSON response received:', responseText.substring(0, 300));
                throw new Error(`Server error: ${res.status} - Expected JSON but got ${contentType || 'unknown format'}`);
            }

            // Check response status
            if (!res.ok) {
                const errorMsg = data?.message || `Request failed with status ${res.status}`;
                throw new Error(errorMsg);
            }

            // Check success flag if data exists
            if (data && !data.success) {
                throw new Error(data.message || 'Failed to delete material');
            }

            const successMsg = data?.message || 'Bahan baku berhasil dihapus.';
            showCustomAlert('Sukses', successMsg, 'success');
            setTimeout(() => window.location.reload(), 1500);
        })
        .catch((err) => {
            console.error('Delete operation error:', err);
            const errorMsg = err.message || 'Terjadi kesalahan saat menghapus data';
            showCustomAlert('Error', errorMsg, 'error');
        });
    });
}

// Event listeners untuk production modal akan dipasang saat modal dibuka
// (Dipindahkan ke function attachProductionEventListeners)

// Handle qty increment/decrement buttons - using delegated listener yang lebih robust
setTimeout(() => {
document.getElementById('qtyIncrement')?.addEventListener('click', function (e) {
    e.preventDefault();
    const qtyInput = document.getElementById('production_qty');
    const max = parseInt(qtyInput.max || '999999', 10);
    const current = parseInt(qtyInput.value || '1', 10);
    const newValue = Math.min(current + 1, max);
    qtyInput.value = newValue;
    renderProductionPreview();
});

document.getElementById('qtyDecrement')?.addEventListener('click', function (e) {
    e.preventDefault();
    const qtyInput = document.getElementById('production_qty');
    const current = parseInt(qtyInput.value || '1', 10);
    const newValue = Math.max(current - 1, 1);
    qtyInput.value = newValue;
    renderProductionPreview();
});
}, 500);

// Event listener untuk production form submission
function attachProductionFormListener() {
    const productionForm = document.getElementById('productionForm');
    
    if (!productionForm) {
        console.warn('productionForm element not found, retrying in 500ms...');
        setTimeout(attachProductionFormListener, 500);
        return;
    }

    productionForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const submitBtn = document.getElementById('productionSubmitBtn');
        const originalText = submitBtn.innerText;

        const itemId = parseInt(document.getElementById('production_item_id').value || '0', 10);
        const inventoryId = parseInt(document.getElementById('production_inventory_id').value || '0', 10);
        const qtyProduced = parseInt(document.getElementById('production_qty').value || '0', 10);
        const notes = document.getElementById('production_notes').value || '';

        const selectedProduct = productionProducts.find((p) => parseInt(p.item_id, 10) === itemId);
        const max = parseInt(selectedProduct?.max_producible || 0, 10);

        console.log('Form submission data:', {
            itemId, inventoryId, qtyProduced, notes,
            selectedProduct, max
        });

        if (!itemId || !inventoryId || qtyProduced <= 0) {
            showCustomAlert('Error', 'Lengkapi data produksi terlebih dahulu.', 'error');
            return;
        }

        if (max <= 0) {
            showCustomAlert('Error', 'Stok bahan baku tidak mencukupi untuk produksi produk ini.', 'error');
            return;
        }

        if (qtyProduced > max) {
            showCustomAlert('Error', `Qty produksi melebihi maksimum (${max}).`, 'error');
            return;
        }

        showConfirm(`Proses produksi sekarang?\n\nSistem akan otomatis mengurangi semua bahan baku sesuai resep dan menambah stok produk jadi.`, function(confirmed) {
            if (!confirmed) return;

            submitBtn.disabled = true;
            submitBtn.innerText = 'Memproses...';

            const payload = {
                item_id: itemId,
                inventory_id: inventoryId,
                qty_produced: qtyProduced,
                notes,
            };

            console.log('Sending payload:', payload);

            fetch(productionExecuteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(async (res) => {
                const data = await res.json();
                console.log('Response status:', res.status);
                console.log('Response data:', data);
                
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Gagal memproses produksi.');
                }
                showCustomAlert('Sukses', data.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            })
            .catch((err) => {
                console.error('Error:', err);
                showCustomAlert('Error', err.message, 'error');
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            });
        });
    });
}

// Pastikan DOM sudah siap sebelum attach listener
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachProductionFormListener);
} else {
    attachProductionFormListener();
}

// Event listener untuk edit material form
function attachEditFormListener() {
    const editForm = document.getElementById('editMaterialForm');
    
    if (!editForm) {
        console.warn('editMaterialForm element not found, retrying in 500ms...');
        setTimeout(attachEditFormListener, 500);
        return;
    }

    editForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const itemRawId = document.getElementById('edit_item_raw_id').value;
        const submitBtn = document.getElementById('editSubmitBtn');
        const originalText = submitBtn.innerText;

        submitBtn.disabled = true;
        submitBtn.innerText = 'Menyimpan...';

        const payload = {
            material_name: document.getElementById('edit_material_name').value,
            unit: document.getElementById('edit_unit').value,
            purchase_price: document.getElementById('edit_purchase_price').value,
            current_stock: document.getElementById('edit_current_stock').value,
            lead_time_days: document.getElementById('edit_lead_time_days').value,
            buffer_stock: document.getElementById('edit_buffer_stock').value,
            supplier_name: document.getElementById('edit_supplier_name').value,
        };

        fetch(buildUrl(updateUrlTemplate, itemRawId), {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Gagal menyimpan perubahan.');
            }
            showCustomAlert('Sukses', data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        })
        .catch((err) => {
            showCustomAlert('Error', err.message, 'error');
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        });
    });
}

// Pastikan DOM sudah siap sebelum attach listener
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachEditFormListener);
} else {
    attachEditFormListener();
}
</script>
</div>

<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4">
    <div class="w-full max-w-2xl bg-white rounded-xl shadow-xl">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Edit Bahan Baku</h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
        </div>

        <form id="editMaterialForm" class="px-6 py-5 space-y-4">
            <input type="hidden" id="edit_item_raw_id" name="item_raw_id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="edit_material_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Bahan</label>
                    <input id="edit_material_name" name="material_name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div>
                    <label for="edit_unit" class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                    <input id="edit_unit" name="unit" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="edit_supplier_name" class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <input id="edit_supplier_name" name="supplier_name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="edit_purchase_price" class="block text-sm font-medium text-gray-700 mb-1">Harga Beli</label>
                    <input id="edit_purchase_price" name="purchase_price" type="number" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div>
                    <label for="edit_current_stock" class="block text-sm font-medium text-gray-700 mb-1">Stok Saat Ini</label>
                    <input id="edit_current_stock" name="current_stock" type="number" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div>
                    <label for="edit_lead_time_days" class="block text-sm font-medium text-gray-700 mb-1">Lead Time (hari)</label>
                    <input id="edit_lead_time_days" name="lead_time_days" type="number" min="0" step="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div>
                    <label for="edit_buffer_stock" class="block text-sm font-medium text-gray-700 mb-1">Buffer Stock</label>
                    <input id="edit_buffer_stock" name="buffer_stock" type="number" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                    Batal
                </button>
                <button type="submit" id="editSubmitBtn" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<div id="productionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4">
    <div class="w-full h-screen max-h-screen bg-white rounded-xl shadow-xl overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
            <h3 class="text-lg font-semibold text-gray-900">Produksi dari Bahan Baku</h3>
            <button type="button" onclick="closeProductionModal()" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
        </div>

        <form id="productionForm" class="px-6 py-5 space-y-4">
            <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-sm text-emerald-900">
                <strong>Pilih produk jadi</strong>, lalu sistem akan otomatis mengurangi bahan baku sesuai resep CSV dan menambah stok finished goods.
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="production_item_id" class="block text-sm font-medium text-gray-700 mb-1">Produk Jadi (BOM)</label>
                    <select id="production_item_id" name="item_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Pilih produk jadi...</option>
                    </select>
                    <p id="production_product_help" class="mt-1 text-xs text-gray-500">Pilih produk jadi, sistem akan menampilkan kebutuhan bahan otomatis.</p>
                </div>
                <div>
                    <label for="production_inventory_id" class="block text-sm font-medium text-gray-700 mb-1">Inventori Tujuan</label>
                    <select id="production_inventory_id" name="inventory_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Pilih inventori...</option>
                    </select>
                </div>
                <div>
                    <label for="production_qty" class="block text-sm font-medium text-gray-700 mb-1">Qty Produksi</label>
                    <div class="flex items-center gap-2">
                        <button type="button" id="qtyDecrement" class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 font-semibold" style="min-width: 40px;">−</button>
                        <input id="production_qty" name="qty_produced" type="number" min="1" step="1" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-center text-lg font-semibold" placeholder="0" required>
                        <button type="button" id="qtyIncrement" class="px-3 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 font-semibold" style="min-width: 40px;">+</button>
                    </div>
                    <p id="qtyHelp" class="mt-1 text-xs text-gray-500">Masukkan atau gunakan tombol +/− untuk mengubah qty</p>
                </div>
                <div class="md:col-span-2">
                    <label for="production_notes" class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                    <textarea id="production_notes" name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Contoh: Produksi rutin shift pagi"></textarea>
                </div>
            </div>

            <div id="productionPreview" class="p-3 rounded-lg bg-gray-50 border border-gray-200 text-sm text-gray-700">
                Pilih produk untuk melihat resep dan estimasi maksimum produksi.
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeProductionModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                    Batal
                </button>
                <button type="submit" id="productionSubmitBtn" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                    Proses Produksi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import Excel -->
<div id="importModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 p-4 overflow-y-auto">
    <div class="w-full h-full flex items-center justify-center">
        <div class="w-full h-full max-w-4xl max-h-screen bg-white rounded-xl shadow-2xl flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-orange-500 to-orange-600 text-white sticky top-0 z-10">
                <h3 class="text-lg font-semibold">Import Data Bahan Baku dari Excel</h3>
                <button type="button" onclick="closeImportModal()" class="text-white hover:text-gray-200 text-2xl leading-none">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <!-- Info Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-900"><strong>📌 Petunjuk:</strong></p>
                    <ul class="text-sm text-blue-800 mt-2 space-y-1 ml-4 list-disc">
                        <li>Gunakan template Excel yang disediakan untuk import</li>
                        <li>Pastikan file format .xlsx dan tidak lebih dari 5MB</li>
                        <li>Isi semua kolom required (Nama Bahan, Harga, Stok, Lead Time, Buffer Stock)</li>
                        <li>Untuk update data lama, isi kolom ID dengan ID bahan yang ada</li>
                        <li>Untuk data baru, biarkan kolom ID kosong (auto-generated)</li>
                    </ul>
                </div>

                <!-- Download Template Link -->
                <div class="text-center py-3 border-t border-b border-gray-200">
                <p class="text-sm text-gray-600 mb-2">Belum punya template?</p>
                <a href="{{ route('admin.inventory.buffer-stock.download-template') }}" class="inline-block px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-semibold">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Unduh Template Excel
                </a>
            </div>

            <!-- File Upload Section -->
            <form id="importForm" class="space-y-4" enctype="multipart/form-data">
                @csrf
                
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                        Pilih File Excel
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-orange-400 hover:bg-orange-50 transition-colors cursor-pointer" 
                         onclick="document.getElementById('excelFile').click()">
                        <i class="bi bi-cloud-arrow-up text-3xl text-gray-400"></i>
                        <p class="mt-2 text-sm text-gray-600">
                            Klik untuk pilih file atau drag & drop di sini
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Format: .xlsx (maksimal 5MB)</p>
                        <input 
                            type="file" 
                            id="excelFile" 
                            name="file" 
                            accept=".xlsx,.xls" 
                            class="hidden"
                            onchange="handleFileSelect(event)"
                        >
                    </div>
                    <p id="fileName" class="mt-2 text-sm text-gray-600"></p>
                </div>

                <!-- Preview Section -->
                <div id="previewSection" class="hidden">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm font-semibold text-gray-900 mb-3">Preview File yang Dipilih:</p>
                        <div class="text-sm text-gray-700">
                            <p>📄 Nama File: <span id="previewFileName" class="font-semibold"></span></p>
                            <p>📊 Ukuran: <span id="previewFileSize" class="font-semibold"></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Footer -->
            <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 bg-gray-50 sticky bottom-0">
                <button 
                    type="button" 
                    onclick="closeImportModal()" 
                    class="px-6 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium">
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        id="importSubmitBtn"
                        class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium flex items-center gap-2">
                        <i class="bi bi-upload"></i> Import Data
                    </button>
            </div>
        </form>
        </div>
    </div>
</div>

</div>

</script>

<!-- Script tambahan untuk import Excel -->
<script>
function openImportModal() {
    const modal = document.getElementById('importModal');
    modal.classList.remove('hidden');
    
    // Reset form
    document.getElementById('importForm').reset();
    document.getElementById('previewSection').classList.add('hidden');
    document.getElementById('fileName').textContent = '';
}

function closeImportModal() {
    const modal = document.getElementById('importModal');
    modal.classList.add('hidden');
    document.getElementById('importForm').reset();
    document.getElementById('previewSection').classList.add('hidden');
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file type
    const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    if (!validTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.xlsx') && !file.name.toLowerCase().endsWith('.xls')) {
        showCustomAlert('Error', 'File harus berformat .xlsx atau .xls', 'error');
        event.target.value = '';
        return;
    }

    // Validate file size (max 5MB)
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        showCustomAlert('Error', 'Ukuran file tidak boleh lebih dari 5MB', 'error');
        event.target.value = '';
        return;
    }

    // Show preview
    document.getElementById('fileName').textContent = '✓ ' + file.name;
    document.getElementById('previewFileName').textContent = file.name;
    document.getElementById('previewFileSize').textContent = (file.size / 1024).toFixed(2) + ' KB';
    document.getElementById('previewSection').classList.remove('hidden');
}

// Handle form submission
document.getElementById('importForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const fileInput = document.getElementById('excelFile');
    const file = fileInput.files[0];

    if (!file) {
        showCustomAlert('Error', 'Pilih file terlebih dahulu', 'error');
        return;
    }

    const formData = new FormData(this);
    const submitBtn = document.getElementById('importSubmitBtn');
    const originalText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengimpor...';

    fetch('{{ route("admin.inventory.buffer-stock.import-excel") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.text().then(text => {
        // Try to parse as JSON
        try {
            return JSON.parse(text);
        } catch (e) {
            // If not JSON, check if it's a redirect (HTML response)
            if (text.includes('<!DOCTYPE') || text.includes('<html')) {
                // Redirect happened, reload page
                return { redirect: true };
            }
            return { error: text };
        }
    }))
    .then(data => {
        if (data.redirect) {
            // Page redirected, likely a redirect with session message
            setTimeout(() => window.location.reload(), 1500);
            return;
        }

        if (data.success) {
            const message = data.imported ? `Berhasil mengimport ${data.imported} barang!` : 'Data berhasil diimpor!';
            showCustomAlert('Sukses', message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            const errorMsg = data.message || 'Gagal mengimpor data';
            showCustomAlert('Error', errorMsg, 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Import error:', error);
        showCustomAlert('Error', 'Terjadi kesalahan: ' + error.message, 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImportModal();
    }
});
</script>

@endsection
