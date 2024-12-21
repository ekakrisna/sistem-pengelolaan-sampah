<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Location
 * 
 * @property int $id
 * @property int $users_id
 * @property string $villages_id
 * @property string $name
 * @property float|null $latitude
 * @property float|null $longitude
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 *
 * @package App\Models
 */
class Location extends Model
{
	use SoftDeletes;
	protected $table = 'locations';

	protected $casts = [
		'users_id' => 'int',
		'latitude' => 'float',
		'longitude' => 'float'
	];

	protected $fillable = [
		'users_id',
		'villages_id',
		'name',
		'latitude',
		'longitude'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'users_id');
	}
}