<?php

namespace App\Services;

use App\Models\MasterItemRawMaterial;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BufferStockExcelService
{
    /**
     * Export buffer stock raw materials ke Excel
     */
    public function exportToExcel(): string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Buffer Stock');

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(12);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(10);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(20);

            // Add header row
            $headers = ['ID', 'Nama Bahan', 'Unit', 'Harga Beli', 'Stok Saat Ini', 'Lead Time (hari)', 'Buffer Stock', 'Supplier'];
            $sheet->fromArray([$headers], null, 'A1');

            // Style header row
            $headerStyle = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Indigo
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
            $sheet->getRowDimension(1)->setRowHeight(25);

            // Get all materials
            $materials = MasterItemRawMaterial::all();

            // Add data rows
            $row = 2;
            foreach ($materials as $material) {
                $sheet->setCellValue('A' . $row, $material->item_raw_id);
                $sheet->setCellValue('B' . $row, $material->material_name ?? '');
                $sheet->setCellValue('C' . $row, $material->unit ?? '');
                $sheet->setCellValue('D' . $row, $material->purchase_price ?? 0);
                $sheet->setCellValue('E' . $row, $material->current_stock ?? 0);
                $sheet->setCellValue('F' . $row, $material->lead_time_days ?? 0);
                $sheet->setCellValue('G' . $row, $material->buffer_stock ?? 0);
                $sheet->setCellValue('H' . $row, $material->supplier_name ?? '');

                // Style data row
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Format numbers
                $sheet->getStyle('D' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

                $row++;
            }

            // Freeze header row
            $sheet->freezePane('A2');

            // Add instruction sheet
            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Petunjuk');
            $this->addInstructionSheet($instructionSheet);

            // Save to temporary file
            $filename = 'buffer_stock_' . date('Y-m-d_H-i-s') . '.xlsx';
            $filepath = storage_path('app/exports/' . $filename);

            // Create directory if not exists
            if (!file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            Log::info("Buffer Stock exported successfully: {$filename}");

            return $filepath;
        } catch (\Exception $e) {
            Log::error("Error exporting buffer stock: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create template Excel untuk import
     */
    public function createImportTemplate(): string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Template Import');

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(12);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(10);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(20);

            // Add header row
            $headers = ['ID', 'Nama Bahan', 'Unit', 'Harga Beli', 'Stok Saat Ini', 'Lead Time (hari)', 'Buffer Stock', 'Supplier'];
            $sheet->fromArray([$headers], null, 'A1');

            // Style header row
            $headerStyle = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981'], // Green
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
            $sheet->getRowDimension(1)->setRowHeight(25);

            // Add sample rows (5 empty rows with formatting)
            for ($row = 2; $row <= 6; $row++) {
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getStyle('D' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            }

            // Freeze header row
            $sheet->freezePane('A2');

            // Add instruction sheet
            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Petunjuk');
            $this->addImportInstructionSheet($instructionSheet);

            // Save to temporary file
            $filename = 'buffer_stock_template_' . date('Y-m-d') . '.xlsx';
            $filepath = storage_path('app/exports/' . $filename);

            // Create directory if not exists
            if (!file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            Log::info("Buffer Stock template created: {$filename}");

            return $filepath;
        } catch (\Exception $e) {
            Log::error("Error creating import template: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Import data dari Excel file
     */
    public function importFromExcel(string $filePath): array
    {
        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $rows = $sheet->toArray();
            $imported = 0;
            $errors = [];

            // Skip header row
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Skip empty rows
                if (empty($row[0]) && empty($row[1])) {
                    continue;
                }

                try {
                    $id = (int)($row[0] ?? null);
                    $material_name = trim($row[1] ?? '');
                    $unit = trim($row[2] ?? '');
                    $purchase_price = (float)($row[3] ?? 0);
                    $current_stock = (float)($row[4] ?? 0);
                    $lead_time_days = (int)($row[5] ?? 0);
                    $buffer_stock = (float)($row[6] ?? 0);
                    $supplier_name = trim($row[7] ?? '');

                    // Validation
                    if (empty($material_name)) {
                        $errors[] = "Baris " . ($i + 1) . ": Nama bahan tidak boleh kosong";
                        continue;
                    }

                    if ($purchase_price < 0 || $current_stock < 0 || $buffer_stock < 0) {
                        $errors[] = "Baris " . ($i + 1) . ": Harga dan stok tidak boleh negatif";
                        continue;
                    }

                    // Determine stock status
                    $stock_status = $current_stock <= 0
                        ? 'critical'
                        : ($current_stock < $buffer_stock ? 'low' : 'normal');

                    // Update or Create
                    if ($id > 0) {
                        MasterItemRawMaterial::updateOrCreate(
                            ['item_raw_id' => $id],
                            [
                                'material_name' => $material_name,
                                'unit' => $unit,
                                'purchase_price' => $purchase_price,
                                'current_stock' => $current_stock,
                                'lead_time_days' => $lead_time_days,
                                'buffer_stock' => $buffer_stock,
                                'supplier_name' => $supplier_name,
                                'stock_status' => $stock_status,
                                'reorder_point' => $buffer_stock,
                                'avg_daily_usage' => 0,
                            ]
                        );
                    } else {
                        // Create without specific ID
                        MasterItemRawMaterial::create([
                            'material_name' => $material_name,
                            'unit' => $unit,
                            'purchase_price' => $purchase_price,
                            'current_stock' => $current_stock,
                            'lead_time_days' => $lead_time_days,
                            'buffer_stock' => $buffer_stock,
                            'supplier_name' => $supplier_name,
                            'stock_status' => $stock_status,
                            'reorder_point' => $buffer_stock,
                            'avg_daily_usage' => 0,
                        ]);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($i + 1) . ": " . $e->getMessage();
                }
            }

            return [
                'success' => true,
                'imported' => $imported,
                'errors' => $errors,
                'message' => "Import selesai: {$imported} bahan berhasil diimpor" .
                    (count($errors) > 0 ? ", " . count($errors) . " error" : "")
            ];
        } catch (\Exception $e) {
            Log::error("Error importing buffer stock: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error saat mengimpor file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Add instruction sheet untuk export
     */
    private function addInstructionSheet(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'Petunjuk Penggunaan File Excel - Buffer Stock');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $instructions = [
            '',
            '📋 Tentang File Ini:',
            'File ini berisi data Buffer Stock Bahan Baku yang telah dibuat dalam sistem.',
            '',
            '📌 Kolom-Kolom:',
            '• ID: Nomor identitas bahan baku (auto-generated)',
            '• Nama Bahan: Nama/jenis bahan baku',
            '• Unit: Satuan ukuran (kg, pcs, liter, dsb)',
            '• Harga Beli: Harga pembelian per unit',
            '• Stok Saat Ini: Jumlah stok yang ada sekarang',
            '• Lead Time (hari): Waktu tunggu pemesanan dari supplier',
            '• Buffer Stock: Stok minimal yang harus dijaga',
            '• Supplier: Nama supplier/pemasok',
            '',
            '💡 Tips Penggunaan:',
            '• Anda dapat mengedit file ini untuk data verification',
            '• Untuk import kembali ke sistem, gunakan template yang disediakan',
            '• Pastikan format data tetap konsisten (harga dan stok menggunakan angka)',
        ];

        $row = 2;
        foreach ($instructions as $instruction) {
            $sheet->setCellValue('A' . $row, $instruction);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(100);
        $sheet->getStyle('A2:A' . ($row - 1))->getAlignment()->setWrapText(true);
    }

    /**
     * Add instruction sheet untuk import template
     */
    private function addImportInstructionSheet(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'Petunjuk Import Data Buffer Stock');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $instructions = [
            '',
            '📋 Cara Menggunakan Template Ini:',
            '',
            '1️⃣ PERSIAPAN:',
            '   • Unduh template ini dari sistem',
            '   • File sudah memiliki header dan format yang benar',
            '',
            '2️⃣ PENGISIAN DATA:',
            '   • Kolom ID: Biarkan kosong untuk data baru (auto-generated)',
            '   • Jika update data lama, isi ID yang sesuai',
            '   • Isi semua kolom dengan data yang akurat',
            '',
            '3️⃣ FORMAT DATA:',
            '   • Nama Bahan: Text (contoh: Tepung Terigu)',
            '   • Unit: Text (contoh: kg, pcs, liter)',
            '   • Harga Beli: Angka desimal (contoh: 12500.50)',
            '   • Stok & Lead Time: Angka bulat positif',
            '   • Buffer Stock: Angka desimal (contoh: 500.00)',
            '',
            '4️⃣ VALIDASI:',
            '   • Pastikan tidak ada kolom required yang kosong',
            '   • Harga dan stok tidak boleh negatif',
            '   • Gunakan titik (.) untuk desimal',
            '',
            '5️⃣ UPLOAD:',
            '   • Simpan file dengan format .xlsx',
            '   • Klik tombol "Import" di halaman Buffer Stock',
            '   • Pilih file yang sudah diisi',
            '   • Sistem akan validasi dan upload data',
            '',
            '⚠️ PENTING:',
            '   • Backup data sebelum import',
            '   • Periksa preview data sebelum konfirmasi',
            '   • Error pada satu baris tidak akan menghentikan proses import baris lain',
        ];

        $row = 2;
        foreach ($instructions as $instruction) {
            $sheet->setCellValue('A' . $row, $instruction);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(100);
        $sheet->getStyle('A2:A' . ($row - 1))->getAlignment()->setWrapText(true);
    }
}
