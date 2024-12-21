<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WasteTransaction
 * 
 * @property int $id
 * @property int $waste_managements_id
 * @property float|null $sub_total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property WasteManagement $waste_management
 *
 * @package App\Models
 */
class WasteTransaction extends Model
{
	protected $table = 'waste_transactions';

	protected $casts = [
		'waste_managements_id' => 'int',
		'sub_total' => 'float'
	];

	protected $fillable = [
		'waste_managements_id',
		'sub_total'
	];

	public function waste_management()
	{
		return $this->belongsTo(WasteManagement::class, 'waste_managements_id');
	}
}
