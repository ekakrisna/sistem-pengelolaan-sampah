<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Province
 * 
 * @property string $code
 * @property string $name
 * @property string|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|City[] $cities
 * @property Collection|UserAddress[] $user_addresses
 *
 * @package App\Models
 */
class Province extends Model
{
	protected $table = 'provinces';
	protected $primaryKey = 'code';
	public $incrementing = false;

	protected $fillable = [
		'name',
		'meta'
	];

	public function cities()
	{
		return $this->hasMany(City::class, 'province_code');
	}

	public function user_addresses()
	{
		return $this->hasMany(UserAddress::class, 'province_code');
	}
}
