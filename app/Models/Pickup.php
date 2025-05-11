<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Pickup
 * 
 * @property int $id
 * @property int $pickup_schedule_id
 * @property int $customer_id
 * @property int|null $petugas_id
 * @property string $status
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Pickup extends Model
{
	use HasFactory;

	protected $table = 'pickups';

	protected $casts = [
		'pickup_schedule_id' => 'int',
		'customer_id' => 'int',
		'petugas_id' => 'int'
	];

	protected $fillable = [
		'pickup_schedule_id',
		'customer_id',
		'petugas_id',
		'status',
		'note'
	];

	public function schedule(): BelongsTo
	{
		return $this->belongsTo(PickupSchedule::class, 'pickup_schedule_id');
	}

	public function customer(): BelongsTo
	{
		return $this->belongsTo(User::class, 'customer_id');
	}

	public function petugas(): BelongsTo
	{
		return $this->belongsTo(User::class, 'petugas_id');
	}

	public function transaction(): HasOne
	{
		return $this->hasOne(Transaction::class);
	}
}
