<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Transaction
 * 
 * @property int $id
 * @property int $customer_id
 * @property string|null $number
 * @property string $status
 * @property float $subtotal
 * @property float $discount_amount
 * @property float $tax_amount
 * @property float $total
 * @property string $currency
 * @property Carbon|null $due_at
 * @property Carbon|null $expires_at
 * @property string|null $description
 * @property array|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Collection|Payment[] $payments
 * @property Collection|TransactionItem[] $transaction_items
 *
 * @package App\Models
 */
class Transaction extends Model
{
	use HasFactory, SoftDeletes;
	protected $table = 'transactions';

	protected $casts = [
		'customer_id' => 'int',
		'subtotal' => 'float',
		'discount_amount' => 'float',
		'tax_amount' => 'float',
		'total' => 'float',
		'due_at' => 'datetime',
		'expires_at' => 'datetime',
		'meta' => 'json',
		'amount_snapshot' => 'float',
		'items_snapshot' => 'json',
		'snapshot_at' => 'datetime',
		'snapshot_version' => 'int',
	];

	protected $fillable = [
		'customer_id',
		'number',
		'status',
		'subtotal',
		'discount_amount',
		'tax_amount',
		'total',
		'currency',
		'due_at',
		'expires_at',
		'description',
		'meta',
		'amount_snapshot',
		'items_snapshot',
		'snapshot_at',
		'snapshot_version',
	];

	public function customer()
	{
		return $this->belongsTo(User::class, 'customer_id');
	}

	public function payments()
	{
		return $this->hasMany(Payment::class);
	}

	public function transaction_items()
	{
		return $this->hasMany(TransactionItem::class);
	}
}
