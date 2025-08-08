<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'pickup_fee_id',
        'description',
        'unit_amount',
        'qty',
        'line_total',
        'meta',
    ];

    protected $casts = [
        'unit_amount' => 'decimal:2',
        'line_total'  => 'decimal:2',
        'meta'        => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function pickupFee()
    {
        return $this->belongsTo(PickupFee::class);
    }
}
