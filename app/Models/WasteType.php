<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WasteType
 * 
 * @property int $id
 * @property int $admin_id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Collection|PickupFee[] $pickup_fees
 * @property Collection|PickupSchedule[] $pickup_schedules
 *
 * @package App\Models
 */
class WasteType extends Model
{
	use SoftDeletes, HasFactory;
	protected $table = 'waste_types';

	protected $casts = [
		'admin_id' => 'int'
	];

	protected $fillable = [
		'admin_id',
		'name',
		'description'
	];

	public function admin()
	{
		return $this->belongsTo(User::class, 'admin_id');
	}

	public function pickup_fees()
	{
		return $this->hasMany(PickupFee::class);
	}

	public function pickup_schedules()
	{
		return $this->hasMany(PickupSchedule::class);
	}
}
