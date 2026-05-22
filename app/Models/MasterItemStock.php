<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterItemStock extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_items_stock';
    protected $primaryKey = 'item_stock_id';
    
    protected $fillable = [
        'item_id',
        'inventory_id',
        'stock',
        'buffer_stock'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'inventory_id' => 'integer',
        'stock' => 'integer',
        'buffer_stock' => 'integer'
    ];

    // Relationship dengan item
    public function item()
    {
        return $this->belongsTo(MasterItem::class, 'item_id', 'item_id');
    }

    // Relationship dengan inventory
    public function inventory()
    {
        return $this->belongsTo(MasterInventory::class, 'inventory_id', 'inventory_id');
    }

    // Relationship untuk raw material in (jika item ini adalah bahan baku)
    public function rawMaterialIns()
    {
        return $this->hasManyThrough(
            RawMaterialIn::class,
            MasterItemRawMaterial::class,
            'item_raw_id',
            'item_raw_id',
            'item_id',
            'item_raw_id'
        );
    }

    // Relationship untuk finished goods in (jika item ini adalah produk jadi)
    public function finishedGoodsIns()
    {
        return $this->hasMany(FinishedGoodsIn::class, 'item_id', 'item_id');
    }
}
