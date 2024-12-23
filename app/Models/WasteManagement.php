<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WasteManagement
 * 
 * @property int $id
 * @property int $users_id
 * @property int $packages_id
 * @property int $locations_id
 * @property string|null $pickup_schedule
 * @property Carbon $pickup_time
 * @property int $status
 * @property Carbon $expired_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Location $location
 * @property Package $package
 * @property User $user
 * @property Collection|WasteTransaction[] $waste_transactions
 *
 * @package App\Models
 */
class WasteManagement extends Model
{
	use HasFactory;

	protected $table = 'waste_managements';

	protected $casts = [
		'users_id' => 'int',
		'packages_id' => 'int',
		'locations_id' => 'int',
		'pickup_time' => 'datetime',
		'status' => 'int',
		'expired_at' => 'datetime'
	];

	protected $fillable = [
		'users_id',
		'packages_id',
		'locations_id',
		'pickup_schedule',
		'pickup_time',
		'status',
		'expired_at'
	];

	public function location()
	{
		return $this->belongsTo(Location::class, 'locations_id');
	}

	public function package()
	{
		return $this->belongsTo(Package::class, 'packages_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'users_id');
	}

	public function waste_transactions()
	{
		return $this->hasMany(WasteTransaction::class, 'waste_managements_id');
	}
}
