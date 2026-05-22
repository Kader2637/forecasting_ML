<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionCode extends Model
{
    use SoftDeletes;

    protected $table = 'production_codes';
    protected $primaryKey = 'production_code_id';
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'item_id',
        'branch_id',
        'code_prefix',
        'current_counter',
        'last_used_date'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'branch_id' => 'integer',
        'current_counter' => 'integer',
        'last_used_date' => 'date'
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(MasterItem::class, 'item_id', 'item_id');
    }

    public function branch()
    {
        return $this->belongsTo(MasterBranch::class, 'branch_id', 'branch_id');
    }
}
