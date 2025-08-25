<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class District
 * 
 * @property string $code
 * @property string $city_code
 * @property string $name
 * @property string|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property City $city
 * @property Collection|UserAddress[] $user_addresses
 * @property Collection|Village[] $villages
 *
 * @package App\Models
 */
class District extends Model
{
	protected $table = 'districts';
	protected $primaryKey = 'code';
	public $incrementing = false;

	protected $fillable = [
		'city_code',
		'name',
		'meta'
	];

	public function city()
	{
		return $this->belongsTo(City::class, 'city_code');
	}

	public function user_addresses()
	{
		return $this->hasMany(UserAddress::class, 'district_code');
	}

	public function villages()
	{
		return $this->hasMany(Village::class, 'district_code');
	}
}
