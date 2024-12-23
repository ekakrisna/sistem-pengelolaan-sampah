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
 * Class PaymentMethod
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|WasteTransactionDetail[] $waste_transaction_details
 *
 * @package App\Models
 */
class PaymentMethod extends Model
{
	use HasFactory;

	protected $table = 'payment_methods';

	protected $fillable = [
		'name',
		'description'
	];

	public function waste_transaction_details()
	{
		return $this->hasMany(WasteTransactionDetail::class, 'payment_methods_id');
	}
}
