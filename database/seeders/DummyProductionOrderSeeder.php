<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterItem;
use App\Models\ProductionOrder;
use Carbon\Carbon;

class DummyProductionOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $finishedGoods = MasterItem::where('status_item', 'active')->get();

        if ($finishedGoods->isEmpty()) {
            $this->command->info('Tidak ada produk jadi ditemukan.');
            return;
        }

        $statuses = ['completed', 'completed', 'completed', 'in_progress', 'draft'];

        $count = 1;
        foreach ($finishedGoods as $item) {
            // Create 1-3 random production orders per finished good
            $numOrders = rand(1, 3);
            
            for ($i = 0; $i < $numOrders; $i++) {
                $status = $statuses[array_rand($statuses)];
                $qty = rand(50, 500);
                $date = Carbon::now()->subDays(rand(1, 60));
                
                ProductionOrder::create([
                    'order_number' => 'PO-' . $date->format('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT),
                    'branch_id' => 1,
                    'created_by' => 1,
                    'item_id' => $item->item_id,
                    'status' => $status,
                    'qty_planned' => $qty,
                    'qty_produced' => $status === 'completed' ? $qty : rand(0, $qty),
                    'planned_date' => $date->toDateString(),
                    'started_at' => $status !== 'draft' ? $date->toDateTimeString() : null,
                    'completed_at' => $status === 'completed' ? $date->copy()->addHours(rand(2, 24))->toDateTimeString() : null,
                    'total_material_cost' => $qty * rand(5000, 15000), // dummy cost
                    'hpp_per_unit' => rand(5000, 15000), // dummy cost
                    'notes' => 'Dummy production data'
                ]);
                $count++;
            }
        }
        
        $this->command->info('Dummy Production Orders seeded successfully.');
    }
}
