<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Payment
 * 
 * @property int $id
 * @property int $transaction_id
 * @property int $customer_id
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property string|null $channel
 * @property string|null $method_code
 * @property string|null $reference_id
 * @property string|null $idempotency_key
 * @property string|null $xendit_account_id
 * @property string|null $xendit_payment_request_id
 * @property string|null $xendit_charge_id
 * @property string|null $xendit_invoice_id
 * @property array|null $va_numbers
 * @property string|null $qris_qr_string
 * @property string|null $checkout_url
 * @property array|null $ewallet_info
 * @property Carbon|null $expires_at
 * @property Carbon|null $paid_at
 * @property string|null $failure_code
 * @property string|null $failure_message
 * @property array|null $xendit_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Transaction $transaction
 *
 * @package App\Models
 */
class Payment extends Model
{
	use HasFactory, SoftDeletes;
	protected $table = 'payments';

	protected $casts = [
		'transaction_id' => 'int',
		'customer_id' => 'int',
		'amount' => 'float',
		'va_numbers' => 'json',
		'ewallet_info' => 'json',
		'expires_at' => 'datetime',
		'paid_at' => 'datetime',
		'xendit_data' => 'json'
	];

	protected $fillable = [
		'transaction_id',
		'customer_id',
		'amount',
		'currency',
		'status',
		'channel',
		'method_code',
		'reference_id',
		'idempotency_key',
		'xendit_account_id',
		'xendit_payment_request_id',
		'xendit_charge_id',
		'xendit_invoice_id',
		'va_numbers',
		'qris_qr_string',
		'checkout_url',
		'ewallet_info',
		'expires_at',
		'paid_at',
		'failure_code',
		'failure_message',
		'xendit_data',
		'split_rule_id'
	];

	public function customer()
	{
		return $this->belongsTo(User::class, 'customer_id');
	}

	public function transaction()
	{
		return $this->belongsTo(Transaction::class);
	}
}
