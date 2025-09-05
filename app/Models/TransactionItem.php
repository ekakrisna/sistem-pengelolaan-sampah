<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TransactionItem
 * 
 * @property int $id
 * @property int $transaction_id
 * @property string $item_type
 * @property int|null $user_address_id
 * @property int|null $pickup_schedule_id
 * @property int|null $pickup_fee_id
 * @property int|null $pickup_id
 * @property string|null $description
 * @property float $unit_amount
 * @property int $qty
 * @property float $line_total
 * @property Carbon|null $current_period_start
 * @property Carbon|null $current_period_end
 * @property array|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property PickupFee|null $pickup_fee
 * @property Pickup|null $pickup
 * @property PickupSchedule|null $pickup_schedule
 * @property Transaction $transaction
 * @property UserAddress|null $user_address
 *
 * @package App\Models
 */
class TransactionItem extends Model
{
	use HasFactory, SoftDeletes;
	protected $table = 'transaction_items';

	protected $casts = [
		'transaction_id' => 'int',
		'user_address_id' => 'int',
		'pickup_schedule_id' => 'int',
		'pickup_fee_id' => 'int',
		'pickup_id' => 'int',
		'unit_amount' => 'float',
		'qty' => 'int',
		'line_total' => 'float',
		'current_period_start' => 'datetime',
		'current_period_end' => 'datetime',
		'meta' => 'json'
	];

	protected $fillable = [
		'transaction_id',
		'item_type',
		'user_address_id',
		'pickup_schedule_id',
		'pickup_fee_id',
		'pickup_id',
		'description',
		'unit_amount',
		'qty',
		'line_total',
		'current_period_start',
		'current_period_end',
		'meta'
	];

	public function pickup_fee()
	{
		return $this->belongsTo(PickupFee::class);
	}

	public function pickup()
	{
		return $this->belongsTo(Pickup::class);
	}

	public function pickup_schedule()
	{
		return $this->belongsTo(PickupSchedule::class);
	}

	public function transaction()
	{
		return $this->belongsTo(Transaction::class);
	}

	public function user_address()
	{
		return $this->belongsTo(UserAddress::class);
	}
}
