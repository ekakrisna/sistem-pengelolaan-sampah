<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WasteManagement
 * 
 * @property int $id
 * @property int $users_id
 * @property int $waste_categories_id
 * @property int $locations_id
 * @property int $packages_id
 * @property Carbon $pickup_time
 * @property int $status
 * @property Carbon $expired_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|WasteTransaction[] $waste_transactions
 *
 * @package App\Models
 */
class WasteManagement extends Model
{
	protected $table = 'waste_managements';

	protected $casts = [
		'users_id' => 'int',
		'waste_categories_id' => 'int',
		'locations_id' => 'int',
		'packages_id' => 'int',
		'pickup_time' => 'datetime',
		'status' => 'int',
		'expired_at' => 'datetime'
	];

	protected $fillable = [
		'users_id',
		'waste_categories_id',
		'locations_id',
		'packages_id',
		'pickup_time',
		'status',
		'expired_at'
	];

	public function waste_transactions()
	{
		return $this->hasMany(WasteTransaction::class, 'waste_managements_id');
	}
}
