<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $rm = new App\Models\MasterItemRawMaterial();
    $rm->material_name = 'Test';
    $rm->unit = 'kg';
    $rm->save();
    echo "SUCCESS: ID is " . $rm->item_raw_id . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
