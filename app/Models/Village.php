<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Village
 * 
 * @property int $id
 * @property string $code
 * @property string $district_code
 * @property string $name
 * @property string|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Village extends Model
{
	protected $table = 'villages';

	protected $casts = [
		'code' => 'int',
		'district_code' => 'int',
		'meta' => 'json'
	];

	protected $fillable = [
		'code',
		'district_code',
		'name',
		'meta'
	];

	public function district(): BelongsTo
	{
		return $this->belongsTo(District::class, 'district_code', 'code');
	}

	public function users(): HasMany
	{
		return $this->hasMany(User::class);
	}
}
