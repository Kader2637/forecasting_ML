<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionPurchase extends Model
{
    protected $table = 'transaction_purchases';
    protected $primaryKey = 'transaction_purchase_id';

    protected $fillable = [
        'supplier_id',
        'branch_id',
        'number',
        'date',
        'subtotal',
        'discount_amount',
        'discount_percentage',
        'total_amount',
        'notes',
        'whatsapp'
    ];

    protected $casts = [
        'date' => 'datetime',
        'total_amount' => 'float'
    ];

    /**
     * Backward-compatible alias for legacy code.
     */
    public function getPurchaseDateAttribute()
    {
        return $this->date;
    }

    /**
     * Backward-compatible alias for legacy code.
     */
    public function getPurchaseNumberAttribute()
    {
        return $this->number;
    }

    public function transactionPayments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class, 'transaction_purchase_id');
    }

    // Commented out until Supplier model is created
    // public function supplier(): BelongsTo
    // {
    //     return $this->belongsTo(Supplier::class, 'supplier_id');
    // }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => 'Unknown'
        };
    }
}
