<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Transaction
 * 
 * @property int $id
 * @property int $pickup_id
 * @property int $payment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Transaction extends Model
{
	use HasFactory, SoftDeletes;

	protected $table = 'transactions';

	protected $fillable = [
		'payment_id',
		'pickup_id',
		'total',
		'description',
	];

	protected $casts = [
		'total' => 'decimal:2',
	];

	// Relasi ke pembayaran
	public function payment()
	{
		return $this->belongsTo(Payment::class);
	}

	// Relasi ke pickup (boleh null jika tidak langsung terkait)
	public function pickup()
	{
		return $this->belongsTo(Pickup::class);
	}
}
