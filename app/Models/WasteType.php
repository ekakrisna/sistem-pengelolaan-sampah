<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class WasteType
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class WasteType extends Model
{
	use HasFactory;

	protected $table = 'waste_types';

	protected $fillable = [
		'name',
		'description'
	];

	public function pickupSchedules(): HasMany
	{
		return $this->hasMany(PickupSchedule::class);
	}
}
