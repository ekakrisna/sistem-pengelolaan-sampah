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
 * Class City
 * 
 * @property int $id
 * @property string $code
 * @property string $province_code
 * @property string $name
 * @property string|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class City extends Model
{
	protected $table = 'cities';

	protected $fillable = [
		'code',
		'province_code',
		'name',
		'meta'
	];

	public function province(): BelongsTo
	{
		return $this->belongsTo(Province::class);
	}

	public function districts(): HasMany
	{
		return $this->hasMany(District::class);
	}

	public function users(): HasMany
	{
		return $this->hasMany(User::class);
	}
}
