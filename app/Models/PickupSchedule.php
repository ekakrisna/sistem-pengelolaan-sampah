<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class PickupSchedule
 * 
 * @property int $id
 * @property int $waste_type_id
 * @property Carbon $date
 * @property string $time_slot
 * @property string|null $location
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class PickupSchedule extends Model
{
	use HasFactory;

	protected $table = 'pickup_schedules';

	protected $casts = [
		'waste_type_id' => 'int',
		'date' => 'datetime',
		'start_pickup_time' => 'datetime:H:i',
		'end_pickup_time' => 'datetime:H:i'
	];

	protected $fillable = [
		'waste_type_id',
		'date',
		'start_pickup_time',
		'end_pickup_time',
		'code_village_pickup',
	];

	public function wasteType(): BelongsTo
	{
		return $this->belongsTo(WasteType::class);
	}

	public function pickups(): HasMany
	{
		return $this->hasMany(Pickup::class);
	}

	public function village(): BelongsTo
	{
		return $this->belongsTo(Village::class, 'code_village_pickup', 'code');
	}
}
