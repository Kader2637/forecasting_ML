@extends('layouts.admin_inventory.app')

@section('title', 'Buat Penyesuaian Stok')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .select2-container .select2-selection--single {
        height: 42px;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 5px 10px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
</style>

<div class="min-h-screen py-8" style="background: linear-gradient(135deg,#f8f5ff 0%,#eef2ff 100%);">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-7 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 flex items-center gap-2">
                    <span class="text-indigo-600"><i class="bi bi-ui-checks"></i></span>
                    Buat Penyesuaian Stok
                </h1>
                <p class="text-gray-500 mt-1 text-sm">Pengecekan stok fisik & sinkronisasi dengan sistem.</p>
            </div>
            <div>
                <a href="{{ route('admin.stock-adjustment.index') }}" class="text-gray-500 hover:text-gray-700 font-semibold flex items-center gap-1">
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
            <form action="{{ route('admin.stock-adjustment.store') }}" method="POST" id="adjustmentForm">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tipe Item <span class="text-red-500">*</span></label>
                        <select name="item_type" id="item_type" class="w-full border-gray-300 rounded-lg shadow-sm px-4 py-2" required>
                            <option value="">-- Pilih Tipe Item --</option>
                            <option value="finished_good">Produk Jadi</option>
                            <option value="raw_material">Bahan Baku</option>
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

                <div class="mb-6" id="fg_container" style="display:none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Produk Jadi <span class="text-red-500">*</span></label>
                    <select name="item_stock_id" id="item_stock_id" class="w-full select2-elem">
                        <option value="">-- Pilih Produk Jadi --</option>
                        @foreach($finishedGoods as $fg)
                            <option value="{{ $fg->item_stock_id }}" data-id="{{ $fg->item_stock_id }}">
                                {{ $fg->item->name_item ?? 'Unknown' }} (Stok: {{ (float) $fg->stock }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6" id="rm_container" style="display:none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Bahan Baku <span class="text-red-500">*</span></label>
                    <select name="item_raw_id" id="item_raw_id" class="w-full select2-elem">
                        <option value="">-- Pilih Bahan Baku --</option>
                        @foreach($rawMaterials as $rm)
                            <option value="{{ $rm->item_raw_id }}" data-id="{{ $rm->item_raw_id }}">
                                {{ $rm->material_name }} (Stok: {{ (float) $rm->current_stock }} {{ $rm->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-2">Stok Sistem Saat Ini</label>
                        <input type="text" id="qty_system" readonly class="w-full border-gray-200 bg-gray-50 rounded-lg shadow-sm px-4 py-2 font-mono text-gray-500" value="0">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-indigo-700 mb-2">Stok Fisik Aktual <span class="text-red-500">*</span></label>
                        <input type="number" name="qty_physical" id="qty_physical" min="0" step="0.01" class="w-full border-indigo-300 ring-indigo-500 rounded-lg shadow-sm px-4 py-2 font-mono font-bold" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Alasan Penyesuaian <span class="text-red-500">*</span></label>
                        <select name="reason" class="w-full border-gray-300 rounded-lg shadow-sm px-4 py-2" required>
                            <option value="">-- Pilih Alasan --</option>
                            <option value="transaksi">Selisih Transaksi / Sistem</option>
                            <option value="cacat">Barang Cacat / Rusak</option>
                            <option value="retur">Retur / Pengembalian</option>
                            <option value="lainnya">Lainnya (Tulis di Catatan)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan</label>
                        <input type="text" name="notes" class="w-full border-gray-300 rounded-lg shadow-sm px-4 py-2" placeholder="Penjelasan detail...">
                    </div>
                </div>

                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-6">
                    <p class="text-sm text-indigo-800 font-medium">
                        <i class="bi bi-info-circle"></i> Info: Menyimpan penyesuaian akan langsung merubah stok sistem untuk menyamai stok fisik aktual yang Anda masukkan.
                    </p>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="bi bi-save"></i> Simpan Penyesuaian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2-elem').select2({ width: '100%' });

    $('#item_type').change(function() {
        var type = $(this).val();
        $('#fg_container').hide();
        $('#rm_container').hide();
        $('#item_stock_id').prop('required', false).val('').trigger('change');
        $('#item_raw_id').prop('required', false).val('').trigger('change');
        $('#qty_system').val('0');

        if (type === 'finished_good') {
            $('#fg_container').show();
            $('#item_stock_id').prop('required', true);
        } else if (type === 'raw_material') {
            $('#rm_container').show();
            $('#item_raw_id').prop('required', true);
        }
    });

    function fetchSystemStock(type, id) {
        if (!id) {
            $('#qty_system').val('0');
            return;
        }
        
        $.ajax({
            url: '{{ route("admin.stock-adjustment.get-system-stock") }}',
            type: 'GET',
            data: { type: type, id: id },
            success: function(res) {
                if(res.success) {
                    $('#qty_system').val(res.system_stock);
                } else {
                    $('#qty_system').val('0');
                }
            }
        });
    }

    $('#item_stock_id').change(function() {
        fetchSystemStock('fg', $(this).val());
    });

    $('#item_raw_id').change(function() {
        fetchSystemStock('rm', $(this).val());
    });
});
</script>
@endsection
