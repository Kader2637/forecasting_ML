<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterItem;
use App\Models\MasterItemRawMaterial;
use App\Models\ProductionOrder;
use App\Models\FinishedGoodsIn;
use App\Models\StockAdjustment;
use App\Models\MasterItemStock;
use App\Models\MasterInventory;
use App\Models\MasterItemBillOfMaterials;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionAndForecastingSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Mulai generate data Production, Forecasting, dan Stock Adjustment...');

        $inventory = MasterInventory::first() ?? MasterInventory::create([
            'name_inventory' => 'Gudang Utama',
            'status_inventory' => 'active'
        ]);

        $item = MasterItem::first();
        if (!$item) {
            $this->command->error('MasterItem kosong. Silakan jalankan MasterItemSeeder terlebih dahulu.');
            return;
        }

        $rawMaterial = MasterItemRawMaterial::first();
        if (!$rawMaterial) {
            $this->command->error('MasterItemRawMaterial kosong.');
            return;
        }

        // 1. Setup BOM if not exist
        $bom = MasterItemBillOfMaterials::where('item_id', $item->item_id)->first();
        if (!$bom) {
            MasterItemBillOfMaterials::create([
                'item_id' => $item->item_id,
                'item_raw_id' => $rawMaterial->item_raw_id,
                'quantity_required' => 50, // 50 units of RM per 1 FG
                'yield_percentage' => 100
            ]);
            $this->command->info('BOM dibuat.');
        }

        // 2. Generate Historical Production Data for ARIMA Forecasting (Past 60 days)
        $this->command->info('Generating 60 days of historical FinishedGoodsIn...');
        $currentDate = Carbon::now()->subDays(60);
        $totalFG = 0;

        for ($i = 0; $i < 60; $i++) {
            // Random production quantity per day
            $qty = rand(10, 50);
            $totalFG += $qty;

            $dateStr = $currentDate->format('Y-m-d H:i:s');
            
            $po = ProductionOrder::create([
                'production_code' => 'PO-' . $currentDate->format('Ymd') . '-' . rand(1000, 9999),
                'item_id' => $item->item_id,
                'status' => 'completed',
                'target_qty' => $qty,
                'actual_qty' => $qty,
                'start_date' => $dateStr,
                'end_date' => $dateStr,
                'notes' => 'Seeder historical data'
            ]);

            FinishedGoodsIn::create([
                'item_id' => $item->item_id,
                'production_order_id' => $po->production_order_id,
                'inventory_id' => $inventory->inventory_id,
                'branch_id' => 1,
                'document_number' => $po->production_code,
                'qty_received' => $qty,
                'unit_cost' => 10000,
                'total_cost' => $qty * 10000,
                'production_date' => $dateStr,
                'received_date' => $dateStr,
                'notes' => 'Seeder historical data'
            ]);

            $currentDate->addDay();
        }

        // Update current stock based on historical production
        $stock = MasterItemStock::firstOrCreate(
            ['item_id' => $item->item_id, 'inventory_id' => $inventory->inventory_id],
            ['stock' => 0, 'buffer_stock' => 0]
        );
        $stock->stock += $totalFG;
        $stock->save();

        // 3. Generate Stock Adjustments (Stock Opname)
        $this->command->info('Generating Stock Adjustments...');
        
        // Defect case
        StockAdjustment::create([
            'item_type' => 'finished_good',
            'item_id' => $item->item_id,
            'inventory_id' => $inventory->inventory_id,
            'branch_id' => 1,
            'document_number' => 'SA-' . date('Ymd') . '-0001',
            'qty_system' => $stock->stock,
            'qty_physical' => $stock->stock - 5,
            'qty_difference' => -5,
            'qty_after_adjustment' => $stock->stock - 5,
            'unit' => 'unit',
            'reason' => 'cacat',
            'adjustment_type' => 'out',
            'adjusted_by' => 1,
            'adjusted_at' => now()->subDays(2),
            'notes' => 'Botol pecah saat di gudang'
        ]);
        $stock->stock -= 5;
        $stock->save();

        // Transaction difference case
        StockAdjustment::create([
            'item_type' => 'raw_material',
            'item_id' => $rawMaterial->item_raw_id,
            'inventory_id' => $inventory->inventory_id,
            'branch_id' => 1,
            'document_number' => 'SA-' . date('Ymd') . '-0002',
            'qty_system' => $rawMaterial->current_stock,
            'qty_physical' => $rawMaterial->current_stock + 10,
            'qty_difference' => 10,
            'qty_after_adjustment' => $rawMaterial->current_stock + 10,
            'unit' => $rawMaterial->unit,
            'reason' => 'transaksi',
            'adjustment_type' => 'in',
            'adjusted_by' => 1,
            'adjusted_at' => now()->subDay(),
            'notes' => 'Sisa bahan baku lebih dari catatan'
        ]);
        $rawMaterial->current_stock += 10;
        $rawMaterial->save();

        $this->command->info('Seeding selesai!');
    }
}
