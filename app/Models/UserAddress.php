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
 * Class UserAddress
 * 
 * @property int $id
 * @property int $user_id
 * @property string $province_code
 * @property string $city_code
 * @property string $district_code
 * @property string $village_code
 * @property string|null $label
 * @property string|null $address_detail
 * @property float|null $lat
 * @property float|null $lng
 * @property bool $is_default
 * @property array|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property City $city
 * @property District $district
 * @property Province $province
 * @property User $user
 * @property Village $village
 * @property Collection|TransactionItem[] $transaction_items
 *
 * @package App\Models
 */
class UserAddress extends Model
{
	use SoftDeletes, HasFactory;
	protected $table = 'user_addresses';

	protected $casts = [
		'user_id' => 'int',
		'lat' => 'float',
		'lng' => 'float',
		'is_default' => 'bool',
		'meta' => 'json'
	];

	protected $fillable = [
		'user_id',
		'province_code',
		'city_code',
		'district_code',
		'village_code',
		'label',
		'address_detail',
		'lat',
		'lng',
		'is_default',
		'meta'
	];

	public function city()
	{
		return $this->belongsTo(City::class, 'city_code');
	}

	public function district()
	{
		return $this->belongsTo(District::class, 'district_code');
	}

	public function province()
	{
		return $this->belongsTo(Province::class, 'province_code');
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function village()
	{
		return $this->belongsTo(Village::class, 'village_code');
	}

	public function transaction_items()
	{
		return $this->hasMany(TransactionItem::class);
	}
}
