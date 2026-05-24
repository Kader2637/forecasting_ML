<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('raw_material_in')) {
            Schema::create('raw_material_in', function (Blueprint $table) {
                $table->id('raw_material_in_id');
                // Relasi
                $table->unsignedBigInteger('item_raw_id')->comment('FK ke master_items_raw_material');
                $table->unsignedBigInteger('supplier_id')->nullable()->comment('FK ke master_suppliers');
                $table->unsignedBigInteger('transaction_purchase_detail_id')->nullable()->comment('FK ke transaction_purchases_details jika dari pembelian');
                $table->unsignedBigInteger('branch_id')->comment('FK ke master_branches (gudang tujuan)');
                $table->unsignedBigInteger('received_by')->comment('FK ke master_users');
                // Identitas dokumen
                $table->string('document_number', 50)->nullable()->comment('Nomor dokumen penerimaan, e.g. RMI-20250701-001');
                $table->string('po_number', 50)->nullable()->comment('Nomor Purchase Order referensi');
                $table->string('batch_number', 50)->nullable()->comment('Nomor batch dari supplier');
                // Kuantitas & harga
                $table->decimal('qty_ordered', 15, 4)->default(0)->comment('Jumlah yang dipesan');
                $table->decimal('qty_received', 15, 4)->default(0)->comment('Jumlah yang diterima (aktual)');
                $table->decimal('qty_rejected', 15, 4)->default(0)->comment('Jumlah yang ditolak/retur');
                $table->string('unit', 30)->nullable()->comment('Satuan: kg, liter, gram, pcs, dll');
                $table->decimal('unit_cost', 15, 4)->default(0)->comment('Harga beli per satuan');
                $table->decimal('total_cost', 15, 2)->default(0)->comment('Total biaya = qty_received * unit_cost');
                // Stok sebelum dan sesudah (untuk audit)
                $table->decimal('stock_before', 15, 4)->default(0)->comment('Stok bahan baku sebelum penerimaan');
                $table->decimal('stock_after', 15, 4)->default(0)->comment('Stok bahan baku sesudah penerimaan');
                // Informasi tambahan
                $table->date('received_date')->comment('Tanggal penerimaan');
                $table->date('expired_date')->nullable()->comment('Tanggal kadaluarsa (jika ada)');
                $table->enum('condition', ['good', 'damaged', 'near_expired'])->default('good')->comment('Kondisi barang diterima');
                $table->string('storage_location', 100)->nullable()->comment('Lokasi penyimpanan di gudang');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('item_raw_id');
                $table->index('supplier_id');
                $table->index('received_date');
                $table->index('batch_number');
            });
        }

        if (!Schema::hasTable('raw_material_out')) {
            Schema::create('raw_material_out', function (Blueprint $table) {
                $table->id('raw_material_out_id');
                // Relasi
                $table->unsignedBigInteger('item_raw_id')->comment('FK ke master_items_raw_material');
                $table->unsignedBigInteger('production_order_id')->nullable()->comment('FK ke production_orders (jika untuk produksi)');
                $table->unsignedBigInteger('bom_id')->nullable()->comment('FK ke master_items_bill_of_materials');
                $table->unsignedBigInteger('branch_id')->comment('FK ke master_branches');
                $table->unsignedBigInteger('issued_by')->comment('FK ke master_users');
                // Identitas dokumen
                $table->string('document_number', 50)->nullable()->comment('Nomor dokumen pengeluaran, e.g. RMO-20250701-001');
                // Kuantitas
                $table->decimal('qty_requested', 15, 4)->default(0)->comment('Jumlah yang diminta');
                $table->decimal('qty_issued', 15, 4)->default(0)->comment('Jumlah yang benar-benar dikeluarkan');
                $table->string('unit', 30)->nullable()->comment('Satuan: kg, liter, gram, pcs, dll');
                $table->decimal('unit_cost', 15, 4)->default(0)->comment('Harga pokok per satuan saat keluar');
                $table->decimal('total_cost', 15, 2)->default(0)->comment('Total biaya = qty_issued * unit_cost');
                // Stok sebelum dan sesudah (untuk audit trail)
                $table->decimal('stock_before', 15, 4)->default(0)->comment('Stok bahan baku sebelum pengeluaran');
                $table->decimal('stock_after', 15, 4)->default(0)->comment('Stok bahan baku sesudah pengeluaran');
                // Alasan & tanggal
                $table->enum('reason', ['production', 'sample', 'waste', 'expired', 'adjustment', 'return_to_supplier', 'other'])
                      ->default('production')
                      ->comment('Alasan pengeluaran bahan baku');
                $table->date('issued_date')->comment('Tanggal pengeluaran');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('item_raw_id');
                $table->index('production_order_id');
                $table->index('issued_date');
                $table->index('reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_in');
        Schema::dropIfExists('raw_material_out');
    }
};
