<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PickupFee
 * 
 * @property int $id
 * @property string $village_code
 * @property int $waste_type_id
 * @property int $admin_id
 * @property float $amount
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Village $village
 * @property WasteType $waste_type
 * @property Collection|TransactionItem[] $transaction_items
 *
 * @package App\Models
 */
class PickupFee extends Model
{
	use HasFactory, SoftDeletes;
	use SoftDeletes;
	protected $table = 'pickup_fees';

	protected $casts = [
		'waste_type_id' => 'int',
		'admin_id' => 'int',
		'amount' => 'float'
	];

	protected $fillable = [
		'village_code',
		'waste_type_id',
		'admin_id',
		'amount',
		'description'
	];

	public function admin()
	{
		return $this->belongsTo(User::class, 'admin_id');
	}

	public function village()
	{
		return $this->belongsTo(Village::class, 'village_code');
	}

	public function waste_type()
	{
		return $this->belongsTo(WasteType::class);
	}

	public function transaction_items()
	{
		return $this->hasMany(TransactionItem::class);
	}
}
