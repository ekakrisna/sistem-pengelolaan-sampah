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
 * Class PickupSchedule
 * 
 * @property int $id
 * @property int $admin_id
 * @property int $waste_type_id
 * @property string $day_of_week
 * @property Carbon $start_pickup_time
 * @property Carbon $end_pickup_time
 * @property string $village_code
 * @property int $quota
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Village $village
 * @property WasteType $waste_type
 * @property Collection|Pickup[] $pickups
 * @property Collection|TransactionItem[] $transaction_items
 *
 * @package App\Models
 */
class PickupSchedule extends Model
{
	use HasFactory, SoftDeletes;
	protected $table = 'pickup_schedules';

	protected $casts = [
		'admin_id' => 'int',
		'waste_type_id' => 'int',
		'start_pickup_time' => 'datetime',
		'end_pickup_time' => 'datetime',
		'quota' => 'int'
	];

	protected $fillable = [
		'admin_id',
		'waste_type_id',
		'day_of_week',
		'start_pickup_time',
		'end_pickup_time',
		'village_code',
		'quota'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'admin_id');
	}

	public function village()
	{
		return $this->belongsTo(Village::class, 'village_code');
	}

	public function waste_type()
	{
		return $this->belongsTo(WasteType::class);
	}

	public function pickups()
	{
		return $this->hasMany(Pickup::class);
	}

	public function transaction_items()
	{
		return $this->hasMany(TransactionItem::class);
	}
}
