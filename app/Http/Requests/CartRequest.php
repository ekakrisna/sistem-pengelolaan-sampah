<?php

namespace App\Http\Requests;

use App\Models\PickupFee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $uid = $this->user()->id ?? null;
        return [
            'transaction_id' => [
                'required',
                'integer',
                Rule::exists('transactions', 'id')
                    ->where(fn($q) => $q->where('customer_id', $uid)
                        ->where('status', 'draft')),
            ],
            'item_type'           => 'sometimes|string|in:pickup,surcharge,discount,tax,other',
            'user_address_id'     => [
                'nullable',
                'integer',
                Rule::exists('user_addresses', 'id')
                    ->where(fn($q) => $q->where('user_id', $uid)),
            ],
            'pickup_schedule_id'  => 'nullable|integer|exists:pickup_schedules,id',
            'pickup_fee_id'       => 'nullable|integer|exists:pickup_fees,id',
            'unit_amount'         => 'nullable|numeric|min:0',
            'qty'                 => 'nullable|integer|min:1',
            'description'         => 'nullable|string|max:191',
            'meta'                => 'nullable|array',
        ];
    }

    /**
     * Tambahkan validasi custom setelah rules dasar lolos.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // hanya validasi jika item_type = pickup dan ada pickup_fee_id
            if ($this->input('item_type', 'pickup') === 'pickup' && $this->filled('pickup_fee_id')) {
                $fee = PickupFee::find($this->pickup_fee_id);

                if ($fee) {
                    $reqUnit = (float) $this->input('unit_amount');
                    $feeAmount = (float) $fee->amount;

                    if ($reqUnit !== $feeAmount) {
                        $validator->errors()->add(
                            'unit_amount',
                            "Unit amount ($reqUnit) does not match the pickup fee price ($feeAmount)."
                        );
                    }
                }
            }
        });
    }
}
