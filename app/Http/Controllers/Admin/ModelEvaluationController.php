<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModelEvaluationController extends Controller
{
    /**
     * Tampilkan halaman utama evaluasi model.
     */
    public function index()
    {
        // Mengambil semua produk aktif dari master_items yang terdaftar di ARIMA forecast summaries
        $masterItems = DB::table('master_items')
            ->where('status_item', 'active')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('arima_forecast_summaries')
                    ->whereColumn('arima_forecast_summaries.produk', 'master_items.code_item');
            })
            ->select('item_id', 'code_item', 'name_item')
            ->orderBy('name_item', 'asc')
            ->get();

        return view('admin_inventory.model_evaluation', compact('masterItems'));
    }

    /**
     * Mengambil data komparasi ARIMA vs Regresi Linear secara dinamis.
     */
    public function getComparisonData($produk)
    {
        try {
            $isAll = $produk === 'all';

            // 1. Ambil data dari tabel arima_forecast_details
            if ($isAll) {
                $details = DB::table('arima_forecast_details')
                    ->select(
                        'date',
                        'data_type',
                        DB::raw('SUM(actual_sales) as actual_sales'),
                        DB::raw('SUM(predicted_sales) as predicted_sales')
                    )
                    ->groupBy('date', 'data_type')
                    ->orderBy('date', 'asc')
                    ->get();
            } else {
                $details = DB::table('arima_forecast_details')
                    ->where('produk', $produk)
                    ->orderBy('date', 'asc')
                    ->get();
            }

            if ($details->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => $isAll 
                        ? 'Tidak ada data evaluasi peramalan di database.' 
                        : "Tidak ada data evaluasi untuk produk '{$produk}' di database."
                ], 404);
            }

            // 2. Bagi baris berdasarkan data_type
            $trainingData = $details->filter(fn($d) => $d->data_type === 'training')->values();
            $actualData = $details->filter(fn($d) => $d->data_type === 'actual')->values();
            $forecastData = $details->filter(fn($d) => $d->data_type === 'forecast')->values();

            $n = count($trainingData);
            if ($n === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data training kosong, gagal melakukan kalkulasi Regresi Linear.'
                ], 422);
            }

            // 3. Fitting Regresi Linear Tren Waktu (y = mx + c) pada data training
            $sumX = 0;
            $sumY = 0;
            $sumXY = 0;
            $sumXX = 0;

            for ($i = 0; $i < $n; $i++) {
                $x = $i + 1;
                $y = (float) $trainingData[$i]->actual_sales;

                $sumX += $x;
                $sumY += $y;
                $sumXY += $x * $y;
                $sumXX += $x * $x;
            }

            $denominator = $n * $sumXX - $sumX * $sumX;
            if ($denominator == 0) {
                $m = 0;
                $c = $sumY / $n;
            } else {
                $m = ($n * $sumXY - $sumX * $sumY) / $denominator;
                $c = ($sumY - $m * $sumX) / $n;
            }

            // 4. Hitung Prediksi Regresi Linear dan Susun Data Grafik
            $dayIndex = 1;

            // Periode Training
            $trainingChart = [];
            foreach ($trainingData as $row) {
                $lrPred = max(0.0, $m * $dayIndex + $c);
                $trainingChart[] = [
                    'date' => substr($row->date, 0, 10),
                    'actual' => (float) $row->actual_sales,
                    'arima_pred' => 0.0,
                    'lr_pred' => round($lrPred, 4)
                ];
                $dayIndex++;
            }

            // Periode Testing (Actual)
            $actualChart = [];
            $tableData = [];
            foreach ($actualData as $row) {
                $lrPred = max(0.0, $m * $dayIndex + $c);
                $actualChart[] = [
                    'date' => substr($row->date, 0, 10),
                    'actual' => (float) $row->actual_sales,
                    'arima_pred' => (float) $row->predicted_sales,
                    'lr_pred' => round($lrPred, 4)
                ];

                // Hitung selisih / error
                $arimaError = (float) $row->actual_sales - (float) $row->predicted_sales;
                $lrError = (float) $row->actual_sales - $lrPred;

                $tableData[] = [
                    'date' => substr($row->date, 0, 10),
                    'actual' => (float) $row->actual_sales,
                    'arima_pred' => (float) $row->predicted_sales,
                    'arima_error' => round($arimaError, 4),
                    'arima_abs_error' => abs(round($arimaError, 4)),
                    'lr_pred' => round($lrPred, 4),
                    'lr_error' => round($lrError, 4),
                    'lr_abs_error' => abs(round($lrError, 4)),
                ];
                $dayIndex++;
            }

            // Periode Future Forecast
            $forecastChart = [];
            foreach ($forecastData as $row) {
                $lrPred = max(0.0, $m * $dayIndex + $c);
                $forecastChart[] = [
                    'date' => substr($row->date, 0, 10),
                    'arima_pred' => (float) $row->predicted_sales,
                    'lr_pred' => round($lrPred, 4)
                ];
                $dayIndex++;
            }

            // 5. Kalkulasi Metrik Akurasi Dinamis (MAE, RMSE, MAPE) pada Testing Period (Actual)
            $k = count($actualData);
            $arimaMae = 0.0;
            $arimaRmse = 0.0;
            $arimaMape = 0.0;

            $lrMae = 0.0;
            $lrRmse = 0.0;
            $lrMape = 0.0;

            if ($k > 0) {
                $arimaSumAbsErr = 0.0;
                $arimaSumSqErr = 0.0;
                $arimaSumPctErr = 0.0;

                $lrSumAbsErr = 0.0;
                $lrSumSqErr = 0.0;
                $lrSumPctErr = 0.0;

                $idxVal = $n + 1;
                foreach ($actualData as $row) {
                    $act = (float) $row->actual_sales;
                    $arima = (float) $row->predicted_sales;
                    $lr = max(0.0, $m * $idxVal + $c);

                    // ARIMA
                    $arimaErr = $act - $arima;
                    $arimaSumAbsErr += abs($arimaErr);
                    $arimaSumSqErr += $arimaErr * $arimaErr;
                    $denom = $act == 0.0 ? 1.0 : $act;
                    $arimaSumPctErr += abs($arimaErr) / $denom;

                    // Regresi Linear
                    $lrErr = $act - $lr;
                    $lrSumAbsErr += abs($lrErr);
                    $lrSumSqErr += $lrErr * $lrErr;
                    $lrSumPctErr += abs($lrErr) / $denom;

                    $idxVal++;
                }

                $arimaMae = $arimaSumAbsErr / $k;
                $arimaRmse = sqrt($arimaSumSqErr / $k);
                $arimaMape = ($arimaSumPctErr / $k) * 100.0;

                $lrMae = $lrSumAbsErr / $k;
                $lrRmse = sqrt($lrSumSqErr / $k);
                $lrMape = ($lrSumPctErr / $k) * 100.0;
            }

            // 6. Siapkan data metadata produk
            $productInfo = [
                'produk' => $isAll ? 'Semua Produk' : $produk,
                'name_item' => $isAll ? 'Keseluruhan Penjualan' : 'Produk Individu',
                'category' => $isAll ? 'Semua Kategori' : '-',
            ];

            if (!$isAll) {
                $itemInfo = DB::table('master_items')
                    ->leftJoin('master_items_categories', 'master_items.item_id', '=', 'master_items_categories.item_id')
                    ->leftJoin('master_categories', 'master_items_categories.categories_id', '=', 'master_categories.category_id')
                    ->where('master_items.code_item', $produk)
                    ->select('master_items.name_item', 'master_categories.name_category')
                    ->first();
                
                if ($itemInfo) {
                    $productInfo['name_item'] = $itemInfo->name_item;
                    $productInfo['category'] = $itemInfo->name_category ?? '-';
                }
            }

            return response()->json([
                'success' => true,
                'summary' => [
                    'produk' => $productInfo['produk'],
                    'name_item' => $productInfo['name_item'],
                    'category' => $productInfo['category'],
                    'arima' => [
                        'mae' => round($arimaMae, 4),
                        'rmse' => round($arimaRmse, 4),
                        'mape' => round($arimaMape, 2),
                    ],
                    'linear_regression' => [
                        'mae' => round($lrMae, 4),
                        'rmse' => round($lrRmse, 4),
                        'mape' => round($lrMape, 2),
                        'slope' => round($m, 4),
                        'intercept' => round($c, 4),
                    ]
                ],
                'chart_data' => [
                    'training' => $trainingChart,
                    'actual' => $actualChart,
                    'forecast' => $forecastChart,
                ],
                'table_data' => $tableData,
            ]);

        } catch (\Exception $e) {
            Log::error("Error on getComparisonData: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
            ], 500);
        }
    }
}
