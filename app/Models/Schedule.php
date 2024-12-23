<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Schedule
 * 
 * @property int $id
 * @property int $waste_categories_id
 * @property int $locations_id
 * @property int $colectors_id
 * @property Carbon|null $pickup_date
 * @property Carbon|null $pickup_time
 * @property int|null $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Location $location
 * @property User $user
 * @property WasteCategory $waste_category
 *
 * @package App\Models
 */
class Schedule extends Model
{
	use HasFactory;

	protected $table = 'schedules';

	protected $casts = [
		'waste_categories_id' => 'int',
		'locations_id' => 'int',
		'colectors_id' => 'int',
		'pickup_date' => 'datetime',
		'pickup_time' => 'datetime',
		'status' => 'int'
	];

	protected $fillable = [
		'waste_categories_id',
		'locations_id',
		'colectors_id',
		'pickup_date',
		'pickup_time',
		'status'
	];

	public function location()
	{
		return $this->belongsTo(Location::class, 'locations_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'colectors_id');
	}

	public function waste_category()
	{
		return $this->belongsTo(WasteCategory::class, 'waste_categories_id');
	}
}
