<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class District
 * 
 * @property string $id
 * @property string $regency_id
 * @property string $name
 * 
 * @property Regency $regency
 * @property Collection|Village[] $villages
 *
 * @package App\Models
 */
class District extends Model
{
	protected $table = 'districts';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'regency_id',
		'name'
	];

	public function regency()
	{
		return $this->belongsTo(Regency::class);
	}

	public function villages()
	{
		return $this->hasMany(Village::class);
	}
}
