<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Package
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property float $price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Package extends Model
{
	protected $table = 'packages';

	protected $casts = [
		'price' => 'float'
	];

	protected $fillable = [
		'name',
		'description',
		'type',
		'price'
	];
}
