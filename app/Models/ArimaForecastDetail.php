<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArimaForecastDetail extends Model
{
    protected $table = 'arima_forecast_details';

    protected $fillable = [
        'date',
        'produk',
        'kategori_mae',
        'actual_sales',
        'predicted_sales',
        'error',
        'absolute_error',
        'data_type',
    ];

    protected $casts = [
        'date' => 'date',
        'actual_sales' => 'decimal:2',
        'predicted_sales' => 'decimal:2',
        'error' => 'decimal:2',
        'absolute_error' => 'decimal:2',
    ];

    /**
     * Get all data untuk produk spesifik
     */
    public static function getProductData($produk, $dataType = null)
    {
        $query = self::where('produk', $produk)
            ->orderBy('date', 'asc');

        if ($dataType) {
            $query->where('data_type', $dataType);
        }

        return $query->get();
    }

    /**
     * Get data for chart (training + actual + forecast)
     */
    public static function getChartData($produk)
    {
        return self::where('produk', $produk)
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('data_type');
    }
}
