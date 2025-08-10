<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PickupFee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pickup_fees';
    public $timestamps = true;

    protected $fillable = [
        'village_code',
        'waste_type_id',
        'admin_id',
        'amount',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relasi ke Village
    public function village()
    {
        return $this->belongsTo(Village::class, 'village_code', 'code');
    }

    // Relasi ke WasteType
    public function wasteType()
    {
        return $this->belongsTo(WasteType::class);
    }

    // Relasi ke Admin (User)
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
