<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ArimaForecastSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $summaryPath = base_path('python/arima_forecast_summary_per_produk.csv');
        $categoryPath = base_path('python/arima_forecast_mae_kategori_ringkas.csv');
        $detailPath = base_path('python/arima_forecast_detailed_per_produk.csv');

        if (!File::exists($summaryPath)) {
            $this->command?->error("File tidak ditemukan: {$summaryPath}");
            return;
        }

        if (!File::exists($categoryPath)) {
            $this->command?->error("File tidak ditemukan: {$categoryPath}");
            return;
        }

        $summaryRows = $this->readCsv($summaryPath);
        $categoryRows = $this->readCsv($categoryPath);
        $detailRows = File::exists($detailPath) ? $this->readCsv($detailPath) : [];

        $summaryPayload = [];
        foreach ($summaryRows as $row) {
            $stationaryRaw = $row['Stationary'] ?? null;
            $stationary = null;

            if ($stationaryRaw !== null && $stationaryRaw !== '') {
                $normalized = mb_strtolower(trim((string) $stationaryRaw));
                if (in_array($normalized, ['ya', 'yes', 'true', '1'], true)) {
                    $stationary = true;
                } elseif (in_array($normalized, ['tidak', 'no', 'false', '0'], true)) {
                    $stationary = false;
                }
            }

            $summaryPayload[] = [
                'produk' => trim((string) ($row['Produk'] ?? '')),
                'arima_order' => trim((string) ($row['ARIMA Order'] ?? '')),
                'mae' => (float) ($row['MAE'] ?? 0),
                'rmse' => (float) ($row['RMSE'] ?? 0),
                'mape_percentage' => (float) ($row['MAPE (%)'] ?? 0),
                'stationary' => $stationary,
                'adf_p_value' => $this->toNullableFloat($row['ADF p-value'] ?? null),
                'kategori_mae' => trim((string) ($row['Kategori MAE'] ?? '')),
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        $summaryPayload = array_values(array_filter($summaryPayload, function (array $row) {
            return $row['produk'] !== '' && $row['kategori_mae'] !== '';
        }));

        $categoryPayload = [];
        foreach ($categoryRows as $row) {
            $kategoriMae = trim((string) ($row['Kategori MAE'] ?? ''));
            if ($kategoriMae === '') {
                continue;
            }

            $categoryPayload[] = [
                'kategori_mae' => $kategoriMae,
                'jumlah_produk' => (int) ($row['jumlah_produk'] ?? 0),
                'mae_rata_rata' => (float) ($row['mae_rata_rata'] ?? 0),
                'rmse_rata_rata' => (float) ($row['rmse_rata_rata'] ?? 0),
                'mape_rata_rata' => (float) ($row['mape_rata_rata'] ?? 0),
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        // Prepare detail payload
        $detailPayload = [];
        if (!empty($detailRows)) {
            foreach ($detailRows as $row) {
                $date = $row['Date'] ?? null;
                if (!$date) {
                    continue;
                }

                try {
                    $detailPayload[] = [
                        'date' => \Carbon\Carbon::parse($date)->format('Y-m-d'),
                        'produk' => trim((string) ($row['Produk'] ?? '')),
                        'kategori_mae' => trim((string) ($row['Kategori_MAE'] ?? '')),
                        'actual_sales' => (float) ($row['Actual_Sales'] ?? 0),
                        'predicted_sales' => (float) ($row['Predicted_Sales'] ?? 0),
                        'error' => (float) ($row['Error'] ?? 0),
                        'absolute_error' => (float) ($row['Absolute_Error'] ?? 0),
                        'data_type' => 'actual',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } catch (\Exception $e) {
                    // Skip jika gagal parse date
                    $this->command?->warn("Error parsing row: " . json_encode($row) . " - " . $e->getMessage());
                    continue;
                }
            }
        }

        DB::transaction(function () use ($summaryPayload, $categoryPayload, $detailPayload) {
            if (!empty($summaryPayload)) {
                DB::table('arima_forecast_summaries')->upsert(
                    $summaryPayload,
                    ['produk'],
                    ['arima_order', 'mae', 'rmse', 'mape_percentage', 'stationary', 'adf_p_value', 'kategori_mae', 'updated_at']
                );
            }

            if (!empty($categoryPayload)) {
                DB::table('arima_forecast_mae_category_summaries')->upsert(
                    $categoryPayload,
                    ['kategori_mae'],
                    ['jumlah_produk', 'mae_rata_rata', 'rmse_rata_rata', 'mape_rata_rata', 'updated_at']
                );
            }

            // Import detail data jika file tersedia
            if (!empty($detailPayload)) {
                DB::table('arima_forecast_details')->upsert(
                    $detailPayload,
                    ['date', 'produk'],
                    ['kategori_mae', 'actual_sales', 'predicted_sales', 'error', 'absolute_error', 'data_type', 'updated_at']
                );
            }
        });

        $this->command?->info('ARIMA forecast CSV berhasil diimport ke database.');
        $this->command?->line(' - arima_forecast_summaries: ' . count($summaryPayload) . ' baris');
        $this->command?->line(' - arima_forecast_mae_category_summaries: ' . count($categoryPayload) . ' baris');
        $this->command?->line(' - arima_forecast_details: ' . count($detailPayload) . ' baris');
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function readCsv(string $path): array
    {
        $rows = [];

        if (($handle = fopen($path, 'r')) === false) {
            return $rows;
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return $rows;
        }

        // Buang UTF-8 BOM jika ada pada header pertama.
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }

            $row = array_combine($headers, $data);
            if ($row === false) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        return (float) $stringValue;
    }
}
