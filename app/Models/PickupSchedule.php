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
use Illuminate\Database\Eloquent\SoftDeletes;

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
	use HasFactory, SoftDeletes;

	protected $table = 'pickup_schedules';
	public $timestamps = true;

	protected $casts = [
		'waste_type_id' => 'int',
		'admin_id' => 'int',
		'date' => 'datetime'
	];

	protected $fillable = [
		'waste_type_id',
		'admin_id',
		'date',
		'start_pickup_time',
		'end_pickup_time',
		'code_village',
		'deleted_at',
		'created_at',
		'updated_at',
	];

	public function wasteType(): BelongsTo
	{
		return $this->belongsTo(WasteType::class);
	}

	public function admin(): BelongsTo
	{
		return $this->belongsTo(User::class, 'admin_id');
	}

	public function pickups(): HasMany
	{
		return $this->hasMany(Pickup::class);
	}

	public function village(): BelongsTo
	{
		return $this->belongsTo(Village::class, 'code_village', 'code');
	}
}
