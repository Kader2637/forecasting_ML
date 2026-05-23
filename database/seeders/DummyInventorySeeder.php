<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterItem;
use App\Models\MasterCategory;
use App\Models\MasterItemRawMaterial;
use App\Models\MasterItemBillOfMaterials;
use App\Models\FinishedGoodsIn;
use App\Models\FinishedGoodsOut;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DummyInventorySeeder extends Seeder
{
    public function run()
    {
        DB::beginTransaction();
        try {
            $companyId = 1;
            $branchId = 1;

            // Categories
            $categories = [];
            for ($i = 1; $i <= 5; $i++) {
                $categories[] = MasterCategory::firstOrCreate(['name_category' => "Kategori Dummy $i"]);
            }

            // Raw Materials
            $raws = [];
            for ($i = 1; $i <= 10; $i++) {
                $raws[] = MasterItemRawMaterial::firstOrCreate(
                    ['material_name' => "Bahan Baku Premium $i"],
                    [
                        'unit' => 'pcs',
                        'purchase_price' => rand(10, 50) * 1000,
                        'current_stock' => rand(100, 1000),
                        'lead_time_days' => rand(2, 7),
                        'buffer_stock' => rand(20, 50),
                        'supplier_name' => "Supplier Dummy $i"
                    ]
                );
            }

            // Master Items
            for ($i = 1; $i <= 25; $i++) {
                $item = MasterItem::firstOrCreate(
                    ['code_item' => "DUMMY-ITEM-00$i"],
                    [
                        'company_id' => $companyId,
                        'name_item' => "Produk Jadi Premium V$i",
                        'netweight_item' => '1 Pcs',
                        'status_item' => 'active',
                        'costprice_item' => rand(20, 80) * 1000,
                        'sellingprice_item' => rand(100, 250) * 1000
                    ]
                );

                // Attach category
                $cat = $categories[array_rand($categories)];
                if (!$item->categories()->where('master_categories.category_id', $cat->category_id)->exists()) {
                    $item->categories()->attach($cat->category_id);
                }

                // Attach BOM
                $r1 = $raws[array_rand($raws)];
                $r2 = $raws[array_rand($raws)];
                MasterItemBillOfMaterials::firstOrCreate(
                    ['item_id' => $item->item_id, 'item_raw_id' => $r1->item_raw_id],
                    ['quantity_required' => rand(1, 5), 'yield_percentage' => 100]
                );
                if ($r1->item_raw_id != $r2->item_raw_id) {
                    MasterItemBillOfMaterials::firstOrCreate(
                        ['item_id' => $item->item_id, 'item_raw_id' => $r2->item_raw_id],
                        ['quantity_required' => rand(1, 5), 'yield_percentage' => 100]
                    );
                }

                // Generate some minimal FinishedGoodsIn & Out so pagination triggers
                for ($j = 0; $j < 3; $j++) {
                    FinishedGoodsIn::create([
                        'item_id' => $item->item_id,
                        'inventory_id' => 1,
                        'branch_id' => $branchId,
                        'received_by' => 1,
                        'qty_received' => rand(10, 50),
                        'received_date' => Carbon::now()->subDays(rand(1, 30)),
                        'production_date' => Carbon::now()->subDays(rand(1, 30)),
                        'document_number' => 'DOC-IN-' . Str::random(5),
                        'batch_number' => 'BATCH-' . Str::random(5),
                        'unit' => 'pcs',
                        'notes' => 'Dummy In'
                    ]);
                }
            }

            DB::commit();
            $this->command->info("Dummy Inventory Data (Master Items, Categories, BOMs) Seeded Successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Failed to seed: " . $e->getMessage());
        }
    }
}
