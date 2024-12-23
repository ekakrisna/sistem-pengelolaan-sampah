<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class User
 * 
 * @property int $id
 * @property int $user_roles_id
 * @property string $phone
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property UserRole $user_role
 * @property Collection|Location[] $locations
 * @property Collection|Schedule[] $schedules
 * @property Collection|WasteManagement[] $waste_managements
 * @property Collection|WasteTransactionDetail[] $waste_transaction_details
 *
 * @package App\Models
 */
class User extends Authenticatable
{
	use HasFactory, Notifiable;

	protected $table = 'users';

	protected $casts = [
		'user_roles_id' => 'int',
		'email_verified_at' => 'datetime'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'user_roles_id',
		'phone',
		'name',
		'email',
		'email_verified_at',
		'password',
		'remember_token'
	];

	public function user_role()
	{
		return $this->belongsTo(UserRole::class, 'user_roles_id');
	}

	public function locations()
	{
		return $this->hasMany(Location::class, 'users_id');
	}

	public function schedules()
	{
		return $this->hasMany(Schedule::class, 'colectors_id');
	}

	public function waste_managements()
	{
		return $this->hasMany(WasteManagement::class, 'users_id');
	}

	public function waste_transaction_details()
	{
		return $this->hasMany(WasteTransactionDetail::class, 'users_id');
	}
}
