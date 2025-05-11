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
		'date' => 'datetime'
	];

	protected $fillable = [
		'waste_type_id',
		'date',
		'time_slot',
		'location'
	];

	public function wasteType(): BelongsTo
	{
		return $this->belongsTo(WasteType::class);
	}

	public function pickups(): HasMany
	{
		return $this->hasMany(Pickup::class);
	}
}
