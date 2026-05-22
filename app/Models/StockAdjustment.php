<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use SoftDeletes;

    protected $table = 'stock_adjustment';
    protected $primaryKey = 'adjustment_id';
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'stock_opname_details_id',
        'stock_opname_batches_id',
        'item_type',
        'item_id',
        'inventory_id',
        'branch_id',
        'document_number',
        'qty_system',
        'qty_physical',
        'qty_difference',
        'qty_after_adjustment',
        'unit',
        'unit_cost',
        'value_impact',
        'reason',
        'adjustment_type',
        'adjusted_by',
        'approved_by',
        'adjusted_at',
        'approved_at',
        'notes'
    ];

    protected $casts = [
        'item_type' => 'string',
        'item_id' => 'integer',
        'inventory_id' => 'integer',
        'branch_id' => 'integer',
        'stock_opname_details_id' => 'integer',
        'stock_opname_batches_id' => 'integer',
        'qty_system' => 'decimal:4',
        'qty_physical' => 'decimal:4',
        'qty_difference' => 'decimal:4',
        'qty_after_adjustment' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'value_impact' => 'decimal:2',
        'adjusted_by' => 'integer',
        'approved_by' => 'integer',
        'adjusted_at' => 'datetime',
        'approved_at' => 'datetime'
    ];

    // Relationships
    public function item()
    {
        // Polymorphic would be better, but for now handle both types
        if ($this->item_type === 'raw_material') {
            return $this->belongsTo(MasterItemRawMaterial::class, 'item_id', 'item_raw_id');
        } else {
            return $this->belongsTo(MasterItem::class, 'item_id', 'item_id');
        }
    }

    public function rawMaterial()
    {
        return $this->belongsTo(MasterItemRawMaterial::class, 'item_id', 'item_raw_id');
    }

    public function inventory()
    {
        return $this->belongsTo(MasterInventory::class, 'inventory_id', 'inventory_id');
    }

    public function adjustedByUser()
    {
        return $this->belongsTo(User::class, 'adjusted_by', 'id');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
}
