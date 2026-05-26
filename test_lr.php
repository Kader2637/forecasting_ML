<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$produk = 'all'; // Let's check 'all' first, as well as some individual products
$isAll = $produk === 'all';

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

$trainingData = $details->filter(fn($d) => $d->data_type === 'training')->values();
$actualData = $details->filter(fn($d) => $d->data_type === 'actual')->values();
$forecastData = $details->filter(fn($d) => $d->data_type === 'forecast')->values();

$n = count($trainingData);
echo "Total Training Data (n): $n\n";
echo "Total Testing Data (k): " . count($actualData) . "\n";
echo "Total Forecast Data: " . count($forecastData) . "\n";

if ($n > 0) {
    // Print first 5 and last 5 training data
    echo "\nFirst 5 Training:\n";
    for ($i = 0; $i < min(5, $n); $i++) {
        echo "  Date: {$trainingData[$i]->date}, Actual: {$trainingData[$i]->actual_sales}\n";
    }
    echo "Last 5 Training:\n";
    for ($i = max(0, $n - 5); $i < $n; $i++) {
        echo "  Date: {$trainingData[$i]->date}, Actual: {$trainingData[$i]->actual_sales}\n";
    }
}

if (count($actualData) > 0) {
    echo "\nFirst 5 Testing (Actual):\n";
    for ($i = 0; $i < min(5, count($actualData)); $i++) {
        echo "  Date: {$actualData[$i]->date}, Actual: {$actualData[$i]->actual_sales}, Predicted ARIMA: {$actualData[$i]->predicted_sales}\n";
    }
}

// Fit Linear Regression
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

echo "\nLR Equation: y = {$m}x + {$c}\n";

// Let's see LR predictions for the testing data
$idxVal = $n + 1;
echo "\nTesting predictions comparison:\n";
$lrSumAbsErr = 0.0;
$lrSumSqErr = 0.0;
$lrSumPctErr = 0.0;
$k = count($actualData);

foreach ($actualData as $row) {
    $act = (float) $row->actual_sales;
    $lr = max(0.0, $m * $idxVal + $c);
    $lrErr = $act - $lr;
    $lrSumAbsErr += abs($lrErr);
    $lrSumSqErr += $lrErr * $lrErr;
    $denom = $act == 0.0 ? 1.0 : $act;
    $lrSumPctErr += abs($lrErr) / $denom;
    
    echo "  Date: {$row->date}, Act: {$act}, LR Pred: {$lr}, Error: {$lrErr}\n";
    $idxVal++;
}

if ($k > 0) {
    $lrMae = $lrSumAbsErr / $k;
    $lrRmse = sqrt($lrSumSqErr / $k);
    $lrMape = ($lrSumPctErr / $k) * 100.0;
    
    echo "\nLR Metrik:\n";
    echo "  MAE: $lrMae\n";
    echo "  RMSE: $lrRmse\n";
    echo "  MAPE: $lrMape%\n";
}

// Let's also check with an individual product like GB-CF01
$item = DB::table('arima_forecast_details')->select('produk')->distinct()->first();
if ($item) {
    echo "\n--- TESTING WITH PRODUCT: {$item->produk} ---\n";
    $produk = $item->produk;
    $details = DB::table('arima_forecast_details')
        ->where('produk', $produk)
        ->orderBy('date', 'asc')
        ->get();
        
    $trainingData = $details->filter(fn($d) => $d->data_type === 'training')->values();
    $actualData = $details->filter(fn($d) => $d->data_type === 'actual')->values();
    
    $n = count($trainingData);
    $k = count($actualData);
    echo "Product Training count: $n, Testing count: $k\n";
    
    // Fit
    $sumX = 0; $sumY = 0; $sumXY = 0; $sumXX = 0;
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
        $m = 0; $c = $sumY / $n;
    } else {
        $m = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $c = ($sumY - $m * $sumX) / $n;
    }
    echo "Product LR: y = {$m}x + {$c}\n";
    
    $idxVal = $n + 1;
    $lrSumAbsErr = 0; $lrSumSqErr = 0; $lrSumPctErr = 0;
    foreach ($actualData as $row) {
        $act = (float) $row->actual_sales;
        $lr = max(0.0, $m * $idxVal + $c);
        $lrErr = $act - $lr;
        $lrSumAbsErr += abs($lrErr);
        $lrSumSqErr += $lrErr * $lrErr;
        $denom = $act == 0.0 ? 1.0 : $act;
        $lrSumPctErr += abs($lrErr) / $denom;
        $idxVal++;
    }
    if ($k > 0) {
        echo "Product LR Metrik:\n";
        echo "  MAE: " . ($lrSumAbsErr / $k) . "\n";
        echo "  RMSE: " . sqrt($lrSumSqErr / $k) . "\n";
        echo "  MAPE: " . (($lrSumPctErr / $k) * 100.0) . "%\n";
    }
}
