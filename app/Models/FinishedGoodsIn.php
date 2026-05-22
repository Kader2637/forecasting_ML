<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinishedGoodsIn extends Model
{
    use SoftDeletes;

    protected $table = 'finished_goods_in';
    protected $primaryKey = 'fg_in_id';
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'item_id',
        'production_order_id',
        'inventory_id',
        'branch_id',
        'received_by',
        'document_number',
        'batch_number',
        'qty_received',
        'unit',
        'unit_cost',
        'total_cost',
        'stock_before',
        'stock_after',
        'production_date',
        'received_date',
        'expired_date',
        'storage_location',
        'qc_status',
        'qc_notes',
        'notes'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'production_order_id' => 'integer',
        'inventory_id' => 'integer',
        'branch_id' => 'integer',
        'received_by' => 'integer',
        'qty_received' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
        'production_date' => 'date',
        'received_date' => 'date',
        'expired_date' => 'date'
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(MasterItem::class, 'item_id', 'item_id');
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id', 'production_order_id');
    }

    public function inventory()
    {
        return $this->belongsTo(MasterInventory::class, 'inventory_id', 'inventory_id');
    }
}
