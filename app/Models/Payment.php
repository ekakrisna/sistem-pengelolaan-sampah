<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Payment
 * 
 * @property int $id
 * @property int $customer_id
 * @property float $amount
 * @property string $status
 * @property string|null $payment_method
 * @property string|null $proof_image
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Payment extends Model
{
	use HasFactory;

	protected $table = 'payments';

	protected $casts = [
		'customer_id' => 'int',
		'amount' => 'float',
		'paid_at' => 'datetime'
	];

	protected $fillable = [
		'customer_id',
		'amount',
		'status',
		'payment_method',
		'proof_image',
		'paid_at'
	];

	public function customer(): BelongsTo
	{
		return $this->belongsTo(User::class, 'customer_id');
	}

	public function transaction(): HasOne
	{
		return $this->hasOne(Transaction::class);
	}
}
