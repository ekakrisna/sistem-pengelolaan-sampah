<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Province
 * 
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Province extends Model
{
	protected $table = 'provinces';

	protected $casts = [
		'code' => 'int',
		'meta' => 'json'
	];

	protected $fillable = [
		'code',
		'name',
		'meta'
	];

	public function cities(): HasMany
	{
		return $this->hasMany(City::class, 'province_code', 'code');
	}

	public function users(): HasMany
	{
		return $this->hasMany(User::class);
	}
}
