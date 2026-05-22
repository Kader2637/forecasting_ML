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
        Schema::create('arima_forecast_details', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('produk')->index();
            $table->string('kategori_mae')->nullable();
            $table->decimal('actual_sales', 12, 2)->nullable();
            $table->decimal('predicted_sales', 12, 2)->nullable();
            $table->decimal('error', 12, 2)->nullable();
            $table->decimal('absolute_error', 12, 2)->nullable();
            $table->enum('data_type', ['training', 'actual', 'forecast'])->default('actual')->index();
            $table->timestamps();
            
            // Index untuk performa query
            $table->index(['produk', 'date']);
            $table->index(['date', 'data_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arima_forecast_details');
    }
};
