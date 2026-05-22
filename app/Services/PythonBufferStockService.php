<?php

namespace App\Services;

use App\Models\MasterItemStock;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service untuk menjalankan Python script update_buffer_stock_db.py
 * Langsung menggunakan buffer stock calculation dari Python
 */
class PythonBufferStockService
{
    private $pythonPath;
    private $scriptPath;
    private $pythonDir;

    public function __construct()
    {
        $this->pythonDir = base_path('python');
        $this->pythonPath = $this->resolvePythonPath();
        $this->scriptPath = $this->pythonDir . DIRECTORY_SEPARATOR . 'update_buffer_stock_db.py';
    }

    /**
     * Resolve path ke Python executable
     */
    private function resolvePythonPath(): string
    {
        // Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $candidates = [
                'python.exe',
                'python3.exe',
                'C:/Python311/python.exe',
                'C:/Python310/python.exe',
                'C:/Python39/python.exe',
            ];
        } else {
            // Linux/Mac
            $candidates = [
                '/usr/bin/python3',
                '/usr/bin/python',
                '/usr/local/bin/python3',
            ];
        }

        foreach ($candidates as $python) {
            if ($this->isPythonAvailable($python)) {
                Log::info("Found Python at: {$python}");
                return $python;
            }
        }

        // Fallback
        $fallback = PHP_OS_FAMILY === 'Windows' ? 'python.exe' : 'python3';
        Log::warning("Using fallback Python path: {$fallback}");
        return $fallback;
    }

    /**
     * Check if Python is available
     */
    private function isPythonAvailable(string $python): bool
    {
        try {
            $output = shell_exec("\"$python\" --version 2>&1");
            return $output !== null && !empty(trim($output));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Jalankan Python script untuk update buffer stock
     */
    public function executeUpdate(int $inventoryId = 1): array
    {
        try {
            if (!file_exists($this->scriptPath)) {
                throw new Exception("Python script tidak ditemukan: {$this->scriptPath}");
            }

            Log::info("Executing Python script: {$this->scriptPath}");
            Log::info("Using Python: {$this->pythonPath}");

            // Set environment variables untuk database connection
            $env = [
                'DB_HOST' => env('DB_HOST', 'localhost'),
                'DB_USERNAME' => env('DB_USERNAME', 'root'),
                'DB_PASSWORD' => env('DB_PASSWORD', ''),
                'DB_DATABASE' => env('DB_DATABASE', 'skripsi_forecasting'),
                'DB_PORT' => env('DB_PORT', '3306'),
                'EXCEL_PATH' => base_path('python/Dataset_Forecasting_ARIMA_Lengkap.xlsx'),
                'PRODUCT_MAPPING_FILE' => base_path('python/product_mapping.json'),
            ];

            // Build command
            $command = $this->buildCommand($env);
            Log::info("Executing command: {$command}");

            // Execute dengan output capture
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            $outputText = implode("\n", $output);
            Log::info("Python script output:\n{$outputText}");
            Log::info("Return code: {$returnCode}");

            // Parse output dan return result
            $result = $this->parseScriptOutput($output, $outputText, $returnCode);

            return $result;

        } catch (Exception $e) {
            Log::error("Error executing Python script: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal menjalankan script Python: ' . $e->getMessage(),
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
                'output' => $e->getMessage()
            ];
        }
    }

    /**
     * Build command untuk execute Python script
     */
    private function buildCommand(array $env): string
    {
        $pythonPath = $this->pythonPath;
        $scriptPath = $this->scriptPath;

        // Build environment variable string
        $envStr = '';
        foreach ($env as $key => $value) {
            if (PHP_OS_FAMILY === 'Windows') {
                $envStr .= "set {$key}=\"{$value}\" && ";
            } else {
                $envStr .= "export {$key}=\"{$value}\"; ";
            }
        }

        // Build command
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: use cmd /c
            $command = "{$envStr}cd /d \"{$this->pythonDir}\" && \"{$pythonPath}\" \"{$scriptPath}\"";
        } else {
            // Linux/Mac: use bash
            $command = "{$envStr}cd \"{$this->pythonDir}\" && \"{$pythonPath}\" \"{$scriptPath}\"";
        }

        return $command;
    }

    /**
     * Parse output dari Python script
     */
    private function parseScriptOutput(array $lines, string $fullOutput, int $returnCode): array
    {
        $stats = [
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // Search untuk pattern dalam output
        
        // Pattern 1: "Successfully updated: X"
        if (preg_match('/Successfully\s+updated[:\s]+(\d+)/i', $fullOutput, $m)) {
            $stats['updated'] = (int)$m[1];
        }
        
        // Pattern 2: "updated_count: X" atau "Updated: X"
        if (preg_match('/updated[:\s_]+count[:\s]+(\d+)|Updated[:\s]+(\d+)/i', $fullOutput, $m)) {
            $stats['updated'] = (int)($m[1] ?? $m[2] ?? $stats['updated']);
        }

        // Pattern 3: "Items not found" atau "skipped: X"
        if (preg_match('/not\s+found[:\s_]+(\d+)|skipped[:\s]+(\d+)/i', $fullOutput, $m)) {
            $stats['skipped'] = (int)($m[1] ?? $m[2] ?? 0);
        }
        
        // Pattern 4: "not_found_count: X"
        if (preg_match('/not_found[:\s_]+count[:\s]+(\d+)/i', $fullOutput, $m)) {
            $stats['skipped'] = (int)$m[1];
        }

        // Pattern 5: "Errors occurred: X"
        if (preg_match('/Errors\s+occurred[:\s]+(\d+)|error[:\s_]+count[:\s]+(\d+)|error[:\s]+(\d+)/i', $fullOutput, $m)) {
            $stats['errors'] = (int)($m[1] ?? $m[2] ?? $m[3] ?? 0);
        }

        $total = $stats['updated'] + $stats['skipped'] + $stats['errors'];

        // Determine success based on return code dan updated count
        $isSuccess = $returnCode === 0 && $stats['updated'] > 0;

        if ($isSuccess) {
            $message = "✅ Buffer Stock berhasil di-update: {$stats['updated']} produk";
            
            if ($stats['skipped'] > 0) {
                $message .= ", {$stats['skipped']} produk tidak ditemukan";
            }
            if ($stats['errors'] > 0) {
                $message .= ", {$stats['errors']} error";
            }

            return [
                'success' => true,
                'message' => $message,
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'errors' => $stats['errors'],
                'total_processed' => $total,
                'full_output' => $fullOutput
            ];
        } elseif ($returnCode !== 0 && !empty(trim($fullOutput))) {
            // Script error
            $errorMsg = "Python script error (code {$returnCode})";
            
            // Try to extract error message
            if (preg_match('/Error[:\s]+(.+?)(?:\n|$)/i', $fullOutput, $m)) {
                $errorMsg = trim($m[1]);
            }

            return [
                'success' => false,
                'message' => "❌ {$errorMsg}",
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'errors' => max($stats['errors'], 1),
                'total_processed' => $total,
                'full_output' => $fullOutput
            ];
        } else {
            // No data updated
            return [
                'success' => false,
                'message' => "⚠️ Tidak ada data yang diupdate (mungkin semua produk sudah ter-match atau tidak ada data)",
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'errors' => $stats['errors'],
                'total_processed' => $total,
                'full_output' => $fullOutput
            ];
        }
    }

    /**
     * Check if Python dependencies are installed
     */
    public function checkDependencies(): array
    {
        $required = ['pymysql', 'pandas', 'openpyxl', 'python-dotenv'];
        $missing = [];

        foreach ($required as $package) {
            $checkCmd = "\"{$this->pythonPath}\" -c \"import " . str_replace('-', '_', $package) . "\" 2>&1";
            $output = [];
            exec($checkCmd, $output, $returnCode);
            
            if ($returnCode !== 0) {
                $missing[] = $package;
            }
        }

        return [
            'status' => empty($missing),
            'missing' => $missing,
            'message' => empty($missing) 
                ? 'All dependencies are installed' 
                : 'Missing packages: ' . implode(', ', $missing)
        ];
    }
}
