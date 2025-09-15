<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PaymentSplitRoute
 * 
 * @property int $id
 * @property int $payment_id
 * @property int $transaction_id
 * @property int|null $admin_id
 * @property string $currency
 * @property int|null $flat_amount
 * @property float|null $percent_amount
 * @property string $destination_account_id
 * @property string $reference_id
 * @property string|null $split_rule_id
 * @property string $status
 * @property Carbon|null $applied_at
 * @property Carbon|null $settled_at
 * @property array|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User|null $user
 * @property Payment $payment
 * @property Transaction $transaction
 *
 * @package App\Models
 */
class PaymentSplitRoute extends Model
{
	protected $table = 'payment_split_routes';

	protected $casts = [
		'payment_id' => 'int',
		'transaction_id' => 'int',
		'admin_id' => 'int',
		'flat_amount' => 'int',
		'percent_amount' => 'float',
		'applied_at' => 'datetime',
		'settled_at' => 'datetime',
		'meta' => 'json'
	];

	protected $fillable = [
		'payment_id',
		'transaction_id',
		'admin_id',
		'currency',
		'flat_amount',
		'percent_amount',
		'destination_account_id',
		'reference_id',
		'split_rule_id',
		'status',
		'applied_at',
		'settled_at',
		'meta'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'admin_id');
	}

	public function payment()
	{
		return $this->belongsTo(Payment::class);
	}

	public function transaction()
	{
		return $this->belongsTo(Transaction::class);
	}
}
