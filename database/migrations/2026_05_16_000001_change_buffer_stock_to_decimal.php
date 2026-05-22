<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_items_raw_material', function (Blueprint $table) {
            // Change buffer_stock column from integer to decimal
            $table->decimal('buffer_stock', 10, 1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_items_raw_material', function (Blueprint $table) {
            // Revert back to integer
            $table->integer('buffer_stock')->change();
        });
    }
};
