<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class District
 * 
 * @property int $id
 * @property string $code
 * @property string $city_code
 * @property string $name
 * @property string|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class District extends Model
{
	protected $table = 'districts';

	protected $fillable = [
		'code',
		'city_code',
		'name',
		'meta'
	];

	public function city(): BelongsTo
	{
		return $this->belongsTo(City::class);
	}

	public function villages(): HasMany
	{
		return $this->hasMany(Village::class);
	}

	public function users(): HasMany
	{
		return $this->hasMany(User::class);
	}
}
