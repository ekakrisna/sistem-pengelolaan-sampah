<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 * 
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Collection|Payment[] $payments
 * @property Collection|PickupFee[] $pickup_fees
 * @property Collection|PickupSchedule[] $pickup_schedules
 * @property Collection|Pickup[] $pickups
 * @property Collection|Transaction[] $transactions
 * @property Collection|UserAddress[] $user_addresses
 * @property Collection|WasteType[] $waste_types
 *
 * @package App\Models
 */
class User extends Authenticatable
{
	use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
	protected $table = 'users';

	protected $casts = [
		'email_verified_at' => 'datetime'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'name',
		'email',
		'phone',
		'email_verified_at',
		'password',
		'role',
		'remember_token'
	];

	public function payments()
	{
		return $this->hasMany(Payment::class, 'customer_id');
	}

	public function pickup_fees()
	{
		return $this->hasMany(PickupFee::class, 'admin_id');
	}

	public function pickup_schedules()
	{
		return $this->hasMany(PickupSchedule::class, 'admin_id');
	}

	public function pickups()
	{
		return $this->hasMany(Pickup::class, 'petugas_id');
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class, 'customer_id');
	}

	public function user_addresses()
	{
		return $this->hasMany(UserAddress::class);
	}

	public function waste_types()
	{
		return $this->hasMany(WasteType::class, 'admin_id');
	}

	public function isSuperAdmin(): bool
	{
		return $this->role === 'super_admin';
	}
	public function isAdmin(): bool
	{
		return $this->role === 'admin';
	}
	public function isPetugas(): bool
	{
		return $this->role === 'petugas';
	}
	public function isCustomer(): bool
	{
		return $this->role === 'customer';
	}
}
