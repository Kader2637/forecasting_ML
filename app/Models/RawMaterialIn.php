<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialIn extends Model
{
    use SoftDeletes;

    protected $table = 'raw_material_in';
    protected $primaryKey = 'raw_material_in_id';
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'item_raw_id',
        'supplier_id',
        'transaction_purchase_detail_id',
        'branch_id',
        'received_by',
        'document_number',
        'po_number',
        'batch_number',
        'qty_ordered',
        'qty_received',
        'qty_rejected',
        'unit',
        'unit_cost',
        'total_cost',
        'stock_before',
        'stock_after',
        'received_date',
        'expired_date',
        'condition',
        'storage_location',
        'notes'
    ];

    protected $casts = [
        'item_raw_id' => 'integer',
        'supplier_id' => 'integer',
        'transaction_purchase_detail_id' => 'integer',
        'branch_id' => 'integer',
        'received_by' => 'integer',
        'qty_ordered' => 'decimal:4',
        'qty_received' => 'decimal:4',
        'qty_rejected' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'stock_before' => 'decimal:4',
        'stock_after' => 'decimal:4',
        'received_date' => 'date',
        'expired_date' => 'date'
    ];

    // Relationships
    public function rawMaterial()
    {
        return $this->belongsTo(MasterItemRawMaterial::class, 'item_raw_id', 'item_raw_id');
    }
}
