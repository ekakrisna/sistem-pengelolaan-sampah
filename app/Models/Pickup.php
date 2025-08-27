<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Pickup
 * 
 * @property int $id
 * @property int $pickup_schedule_id
 * @property int $customer_id
 * @property int|null $petugas_id
 * @property string $status
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User|null $user
 * @property PickupSchedule $pickup_schedule
 * @property Collection|TransactionItem[] $transaction_items
 *
 * @package App\Models
 */
class Pickup extends Model
{
	use HasFactory, SoftDeletes;
	protected $table = 'pickups';

	protected $casts = [
		'pickup_schedule_id' => 'int',
		'customer_id' => 'int',
		'petugas_id' => 'int'
	];

	protected $fillable = [
		'pickup_schedule_id',
		'customer_id',
		'petugas_id',
		'status',
		'note'
	];

	public function customer()
	{
		return $this->belongsTo(User::class, 'customer_id');
	}

	public function petugas()
	{
		return $this->belongsTo(User::class, 'petugas_id');
	}

	public function pickup_schedule()
	{
		return $this->belongsTo(PickupSchedule::class);
	}

	public function transaction_items()
	{
		return $this->hasMany(TransactionItem::class);
	}
}
