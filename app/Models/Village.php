<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Village
 * 
 * @property string $code
 * @property string $district_code
 * @property string $name
 * @property string|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property District $district
 * @property Collection|PickupFee[] $pickup_fees
 * @property Collection|PickupSchedule[] $pickup_schedules
 * @property Collection|UserAddress[] $user_addresses
 *
 * @package App\Models
 */
class Village extends Model
{
	protected $table = 'villages';
	protected $primaryKey = 'code';
	public $incrementing = false;

	protected $fillable = [
		'district_code',
		'name',
		'meta'
	];

	public function district()
	{
		return $this->belongsTo(District::class, 'district_code');
	}

	public function pickup_fees()
	{
		return $this->hasMany(PickupFee::class, 'village_code');
	}

	public function pickup_schedules()
	{
		return $this->hasMany(PickupSchedule::class, 'village_code');
	}

	public function user_addresses()
	{
		return $this->hasMany(UserAddress::class, 'village_code');
	}
}
