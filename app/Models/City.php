<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class City
 * 
 * @property string $code
 * @property string $province_code
 * @property string $name
 * @property string|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Province $province
 * @property Collection|District[] $districts
 * @property Collection|UserAddress[] $user_addresses
 *
 * @package App\Models
 */
class City extends Model
{
	protected $table = 'cities';
	protected $primaryKey = 'code';
	public $incrementing = false;

	protected $fillable = [
		'province_code',
		'name',
		'meta'
	];

	protected $casts = [
		'meta' => 'json'
	];

	public function province()
	{
		return $this->belongsTo(Province::class, 'province_code');
	}

	public function districts()
	{
		return $this->hasMany(District::class, 'city_code');
	}

	public function user_addresses()
	{
		return $this->hasMany(UserAddress::class, 'city_code');
	}
}
