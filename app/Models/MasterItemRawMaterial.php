<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterItemRawMaterial extends Model
{
    use SoftDeletes;

    protected $table = 'master_items_raw_material';
    protected $primaryKey = 'item_raw_id';
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'material_name',
        'unit',
        'purchase_price',
        'current_stock',
        'avg_daily_usage',
        'last_reorder_date',
        'stock_status',
        'lead_time_days',
        'buffer_stock',
        'reorder_point',
        'supplier_name'
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'current_stock' => 'decimal:1',
        'avg_daily_usage' => 'decimal:1',
        'last_reorder_date' => 'date',
        'lead_time_days' => 'integer',
        'buffer_stock' => 'decimal:1',
        'reorder_point' => 'decimal:1',
    ];

    // Relationships
    public function billOfMaterials()
    {
        return $this->hasMany(MasterItemBillOfMaterials::class, 'item_raw_id', 'item_raw_id');
    }

    public function rawMaterialIn()
    {
        return $this->hasMany(RawMaterialIn::class, 'item_raw_id', 'item_raw_id');
    }

    public function rawMaterialOut()
    {
        return $this->hasMany(RawMaterialOut::class, 'item_raw_id', 'item_raw_id');
    }

    public function stockAdjustments()
    {
        // StockAdjustment uses item_id + item_type, not item_raw_id directly
        return $this->hasMany(StockAdjustment::class, 'item_id', 'item_raw_id')
                    ->where('item_type', 'raw_material');
    }
}
