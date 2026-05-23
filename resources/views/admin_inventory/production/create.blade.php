@extends('layouts.admin_inventory.app')

@section('title', 'Buat Produksi Baru')

@section('content')

<div class="min-h-screen py-8" style="background: linear-gradient(135deg,#f8f5ff 0%,#eef2ff 100%);">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-7 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 flex items-center gap-2">
                    <span class="text-purple-600"><i class="bi bi-tools"></i></span>
                    Buat Produksi Baru
                </h1>
                <p class="text-gray-500 mt-1 text-sm">Produksi barang jadi berdasarkan Bill of Materials (BOM).</p>
            </div>
            <div>
                <a href="{{ route('admin.production.index') }}" class="text-gray-500 hover:text-gray-700 font-semibold flex items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ session('error') }}
        </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <form action="{{ route('admin.production.store') }}" method="POST" id="productionForm">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Produk Jadi <span class="text-red-500">*</span></label>
                        <select name="item_id" id="item_id" class="w-full" required>
                            <option value="">-- Pilih Produk --</option>
                            @foreach($finishedGoods as $item)
                                <option value="{{ $item->item_id }}" {{ $targetItemId == $item->item_id ? 'selected' : '' }}>
                                    {{ $item->code_item }} - {{ $item->name_item }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Lokasi Inventori <span class="text-red-500">*</span></label>
                        <select name="inventory_id" id="inventory_id" class="w-full border-gray-300 rounded-lg shadow-sm px-4 py-2" required>
                            @foreach($inventories as $inv)
                                <option value="{{ $inv->inventory_id }}">{{ $inv->name_inventory }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Kuantitas Produksi <span class="text-red-500">*</span></label>
                        <input type="number" name="qty" id="qty" value="{{ $targetQty ?? 1 }}" min="1" step="1" class="w-full border-gray-300 rounded-lg shadow-sm px-4 py-2" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan (Opsional)</label>
                        <input type="text" name="notes" class="w-full border-gray-300 rounded-lg shadow-sm px-4 py-2" placeholder="Catatan produksi...">
                    </div>
                </div>
                
                <div class="flex items-end mb-6">
                    <button type="button" id="btnCalculate" class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 font-semibold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="bi bi-calculator"></i> Kalkulasi Bahan Baku
                    </button>
                </div>

                <!-- Bom Result Section -->
                <div id="bomResultSection" style="display:none;" class="mb-6 border border-gray-200 rounded-xl overflow-hidden">
                    <div class="bg-slate-50 px-4 py-3 border-b border-gray-200 font-semibold text-gray-700 flex justify-between items-center">
                        <span>Kebutuhan Bahan Baku</span>
                        <span id="productionStatusBadge"></span>
                    </div>
                    <table class="min-w-full text-sm">
                        <thead class="bg-white border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Bahan Baku</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Dibutuhkan</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Stok Tersedia</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody id="bomTableBody" class="bg-white divide-y divide-gray-50">
                            <!-- filled by JS -->
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100">
                    <button type="submit" id="btnSubmit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg flex items-center gap-2 transition-colors opacity-50 cursor-not-allowed" disabled>
                        <i class="bi bi-check-lg"></i> Proses Produksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#item_id').select2();

    function calculateBom() {
        var itemId = $('#item_id').val();
        var qty = $('#qty').val();

        if(!itemId || !qty) {
            alert('Silakan pilih produk dan kuantitas terlebih dahulu.');
            return;
        }

        var btn = $('#btnCalculate');
        btn.html('<i class="bi bi-hourglass-split"></i> Menghitung...').prop('disabled', true);

        $.ajax({
            url: '{{ route("admin.production.calculateBom") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                item_id: itemId,
                qty: qty
            },
            success: function(res) {
                btn.html('<i class="bi bi-calculator"></i> Kalkulasi Ulang').prop('disabled', false);
                if(res.success) {
                    $('#bomResultSection').show();
                    var tbody = $('#bomTableBody');
                    tbody.empty();
                    
                    if(res.materials.length === 0) {
                        tbody.append('<tr><td colspan="4" class="text-center py-4 text-gray-500">Tidak ada data BOM.</td></tr>');
                    } else {
                        res.materials.forEach(function(mat) {
                            var statusIcon = mat.enough 
                                ? '<span class="text-green-600"><i class="bi bi-check-circle-fill"></i> Cukup</span>'
                                : '<span class="text-red-600"><i class="bi bi-x-circle-fill"></i> Kurang</span>';
                            
                            var row = `<tr>
                                <td class="px-4 py-3 font-medium text-gray-800">${mat.name}</td>
                                <td class="px-4 py-3 text-right font-mono">${mat.required} ${mat.unit}</td>
                                <td class="px-4 py-3 text-right font-mono">${mat.available} ${mat.unit}</td>
                                <td class="px-4 py-3 text-center">${statusIcon}</td>
                            </tr>`;
                            tbody.append(row);
                        });
                    }

                    if(res.can_produce && res.materials.length > 0) {
                        $('#productionStatusBadge').html('<span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Bisa Diproduksi</span>');
                        $('#btnSubmit').removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
                    } else {
                        $('#productionStatusBadge').html('<span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">Bahan Baku Kurang</span>');
                        $('#btnSubmit').addClass('opacity-50 cursor-not-allowed').prop('disabled', true);
                    }
                } else {
                    alert(res.message);
                    $('#bomResultSection').hide();
                }
            },
            error: function() {
                btn.html('<i class="bi bi-calculator"></i> Kalkulasi Bahan Baku').prop('disabled', false);
                alert('Terjadi kesalahan jaringan.');
            }
        });
    }

    $('#btnCalculate').click(calculateBom);

    // Auto calculate if loaded with pre-filled data
    if($('#item_id').val() && $('#qty').val()) {
        calculateBom();
    }
});
</script>
@endsection
