<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Village
 * 
 * @property string $id
 * @property string $district_id
 * @property string $name
 * 
 * @property District $district
 * @property Collection|Location[] $locations
 *
 * @package App\Models
 */
class Village extends Model
{
	use HasFactory;

	protected $table = 'villages';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'district_id',
		'name'
	];

	public function district()
	{
		return $this->belongsTo(District::class);
	}

	public function locations()
	{
		return $this->hasMany(Location::class, 'villages_id');
	}
}
