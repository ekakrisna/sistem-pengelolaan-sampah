<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
	use HasFactory;

	protected $table = 'transactions';

	protected $casts = [
		'pickup_id' => 'int',
		'payment_id' => 'int'
	];

	protected $fillable = [
		'pickup_id',
		'payment_id'
	];

	public function pickup(): BelongsTo
	{
		return $this->belongsTo(Pickup::class);
	}

	public function payment(): BelongsTo
	{
		return $this->belongsTo(Payment::class);
	}
}
