<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Province
 * 
 * @property string $id
 * @property string $name
 * 
 * @property Collection|Regency[] $regencies
 *
 * @package App\Models
 */
class Province extends Model
{
	use HasFactory;

	protected $table = 'provinces';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'name'
	];

	public function regencies()
	{
		return $this->hasMany(Regency::class);
	}
}
