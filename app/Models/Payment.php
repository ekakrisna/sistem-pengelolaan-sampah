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
use Illuminate\Database\Eloquent\SoftDeletes;

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
	use HasFactory, SoftDeletes;

	protected $table = 'payments';

	protected $fillable = [
		'customer_id',
		'amount',
		'status',
		'payment_method',
		'external_id',
		'invoice_url',
		'xendit_data',
		'paid_at',
	];

	protected $casts = [
		'xendit_data' => 'array',
		'paid_at' => 'datetime',
	];

	// Relasi ke customer
	public function customer()
	{
		return $this->belongsTo(User::class, 'customer_id');
	}

	// Relasi ke transaction
	public function transaction()
	{
		return $this->hasOne(Transaction::class);
	}

	// Scope: payment yang masih belum dibayar
	public function scopePending($query)
	{
		return $query->where('status', 'pending');
	}

	// Scope: payment yang berhasil
	public function scopePaid($query)
	{
		return $query->where('status', 'paid');
	}
}
