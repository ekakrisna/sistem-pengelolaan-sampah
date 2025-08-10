<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WasteType
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class WasteType extends Model
{
	use HasFactory, SoftDeletes;

	protected $table = 'waste_types';
	protected $primaryKey = 'id';
	public $timestamps = true;

	protected $casts = [
		'admin_id' => 'int'
	];

	protected $fillable = [
		'name',
		'description',
		'admin_id',
		'deleted_at',
		'created_at',
		'updated_at',
	];

	public function admin()
	{
		return $this->belongsTo(User::class, 'admin_id');
	}

	public function pickupSchedules(): HasMany
	{
		return $this->hasMany(PickupSchedule::class);
	}
}
