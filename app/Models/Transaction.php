<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
	use HasFactory, SoftDeletes;

	protected $table = 'transactions';
	public $timestamps = true;

	protected $fillable = [
		'pickup_id',
		'status',
		'total',
		'description',
		'deleted_at',
		'created_at',
		'updated_at',
	];

	protected $casts = [
		'total' => 'decimal:2',
	];

	public function payment()
	{
		return $this->hasOne(Payment::class);
	}

	public function pickup()
	{
		return $this->belongsTo(Pickup::class);
	}

	public function items()
	{
		return $this->hasMany(TransactionItem::class);
	}
}
