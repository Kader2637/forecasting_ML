<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialOut extends Model
{
    use SoftDeletes;

    protected $table = 'raw_material_out';
    protected $primaryKey = 'raw_material_out_id';
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'item_raw_id',
        'production_order_id',
        'bom_id',
        'branch_id',
        'issued_by',
        'document_number',
        'qty_requested',
        'qty_issued',
        'unit',
        'unit_cost',
        'total_cost',
        'stock_before',
        'stock_after',
        'reason',
        'issued_date',
        'notes'
    ];

    protected $casts = [
        'item_raw_id' => 'integer',
        'production_order_id' => 'integer',
        'bom_id' => 'integer',
        'branch_id' => 'integer',
        'issued_by' => 'integer',
        'qty_requested' => 'decimal:4',
        'qty_issued' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'stock_before' => 'decimal:4',
        'stock_after' => 'decimal:4',
        'issued_date' => 'date'
    ];

    // Relationships
    public function rawMaterial()
    {
        return $this->belongsTo(MasterItemRawMaterial::class, 'item_raw_id', 'item_raw_id');
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id', 'production_order_id');
    }
}
