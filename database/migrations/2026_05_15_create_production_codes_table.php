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
        // Tabel untuk tracking production codes dan counter mereka
        Schema::create('production_codes', function (Blueprint $table) {
            $table->id('production_code_id');
            // Relasi
            $table->unsignedBigInteger('item_id')->comment('FK ke master_items (barang jadi)');
            $table->unsignedBigInteger('branch_id')->comment('FK ke master_branches');
            // Kode produksi
            $table->string('code_prefix', 10)->comment('Prefix kode produksi, e.g. DS, CNF, GF, dll');
            $table->integer('current_counter')->default(0)->comment('Counter saat ini untuk tanggal tertentu');
            $table->date('last_used_date')->comment('Tanggal terakhir kode ini digunakan');
            // Tracking
            $table->timestamps();
            $table->softDeletes();
            // Index
            $table->unique(['item_id', 'branch_id']);
            $table->index('code_prefix');
            $table->index('last_used_date');
        });

        // Tambah kolom production_code ke finished_goods_in
        Schema::table('finished_goods_in', function (Blueprint $table) {
            $table->string('production_code', 50)->nullable()->after('batch_number')->comment('Kode produksi, e.g. DS3516, CNF021078');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finished_goods_in', function (Blueprint $table) {
            $table->dropColumn('production_code');
        });

        Schema::dropIfExists('production_codes');
    }
};
