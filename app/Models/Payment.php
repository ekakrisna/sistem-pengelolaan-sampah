<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
	use HasFactory, SoftDeletes;

	protected $table = 'payments';
	public $timestamps = true;

	protected $fillable = [
		'customer_id',
		'amount',
		'status',
		'payment_method',
		'external_id',
		'invoice_url',
		'xendit_data',
		'paid_at',
		'deleted_at',
		'created_at',
		'updated_at',
	];

	protected $casts = [
		'xendit_data' => 'array',
		'paid_at' => 'datetime',
		'amount' => 'decimal:2',
	];

	public function customer()
	{
		return $this->belongsTo(User::class, 'customer_id');
	}

	// public function transactions()
	// {
	// 	return $this->hasMany(Transaction::class);
	// }

	public function transaction()
	{
		return $this->hasOne(Transaction::class);
	}

	public function scopePending($query)
	{
		return $query->where('status', 'pending');
	}

	public function scopePaid($query)
	{
		return $query->where('status', 'paid');
	}
}
