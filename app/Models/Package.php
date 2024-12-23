<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Package
 * 
 * @property int $id
 * @property string $name
 * @property string $type
 * @property float $price
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|WasteManagement[] $waste_managements
 *
 * @package App\Models
 */
class Package extends Model
{
	use HasFactory;

	protected $table = 'packages';

	protected $casts = [
		'price' => 'float'
	];

	protected $fillable = [
		'name',
		'type',
		'price',
		'description'
	];

	public function waste_managements()
	{
		return $this->hasMany(WasteManagement::class, 'packages_id');
	}
}
