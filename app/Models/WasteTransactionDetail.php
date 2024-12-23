<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WasteTransactionDetail
 * 
 * @property int $id
 * @property int $users_id
 * @property int $waste_transactions_id
 * @property int $payment_methods_id
 * @property float $total_price
 * @property string|null $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property PaymentMethod $payment_method
 * @property User $user
 * @property WasteTransaction $waste_transaction
 *
 * @package App\Models
 */
class WasteTransactionDetail extends Model
{
	use HasFactory;

	protected $table = 'waste_transaction_details';

	protected $casts = [
		'users_id' => 'int',
		'waste_transactions_id' => 'int',
		'payment_methods_id' => 'int',
		'total_price' => 'float'
	];

	protected $fillable = [
		'users_id',
		'waste_transactions_id',
		'payment_methods_id',
		'total_price',
		'status'
	];

	public function payment_method()
	{
		return $this->belongsTo(PaymentMethod::class, 'payment_methods_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'users_id');
	}

	public function waste_transaction()
	{
		return $this->belongsTo(WasteTransaction::class, 'waste_transactions_id');
	}
}
