<?php

namespace App\Console\Commands;

use App\Services\BufferStockRopCalculationService;
use Illuminate\Console\Command;

class CalculateBufferStockRop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'buffer-stock:calculate-rop {--inventory-id=1 : Inventory ID untuk di-update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate dan update buffer stock dengan ROP langsung dari database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inventoryId = $this->option('inventory-id');

        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║  CALCULATE BUFFER STOCK dengan ROP (Reorder Point)         ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');
        $this->line("Inventory ID: <fg=cyan>$inventoryId</>");
        $this->line("Lead Time: <fg=cyan>5.4 hari (avg)</> / <fg=cyan>7 hari (max)</>");
        $this->line('');

        $service = new BufferStockRopCalculationService();

        try {
            $this->info('Processing items...');
            $this->line('');

            $bar = $this->output->createProgressBar(1);
            $bar->start();

            $result = $service->calculateAndUpdateAllItems($inventoryId);

            $bar->finish();
            $this->line('');
            $this->line('');

            // Display results
            $this->info('╔════════════════════════════════════════════════════════════╗');
            $this->info('║  RESULT SUMMARY                                            ║');
            $this->info('╚════════════════════════════════════════════════════════════╝');
            $this->line('');
            $this->line("  Total Items Processed  : <fg=cyan>{$result['total']}</>");
            $this->line("  ✓ Successfully Updated : <fg=green>{$result['updated']}</>");
            $this->line("  ⊘ Skipped (no demand)  : <fg=yellow>{$result['skipped']}</>");
            $this->line("  ✗ Errors               : <fg=red>{$result['errors']}</>");
            $this->line('');

            // Display statistics
            $stats = $service->getSummaryStatistics($inventoryId);
            $this->info('╔════════════════════════════════════════════════════════════╗');
            $this->info('║  DATABASE SUMMARY                                          ║');
            $this->info('╚════════════════════════════════════════════════════════════╝');
            $this->line('');
            $this->line("  Total Items in Inventory    : <fg=cyan>{$stats['total_items']}</>");
            $this->line("  Total ROP Sum               : <fg=cyan>" . number_format($stats['total_rop_sum']) . "</>");
            $this->line("  Average ROP per Item        : <fg=cyan>" . $stats['avg_rop'] . "</>");
            $this->line("  Items Needing Order         : <fg=red>{$stats['items_needs_order']}</>");
            $this->line("  Items with Safe Stock       : <fg=green>{$stats['items_safe']}</>");
            $this->line('');

            if ($result['errors'] === 0) {
                $this->info('✓ Process completed successfully!');
            } else {
                $this->warn('⚠ Process completed with some errors. Check logs.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
