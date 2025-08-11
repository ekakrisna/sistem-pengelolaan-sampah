<?php
// app/Data/PaymentMethod/CustomerInlineData.php
namespace App\Data\PaymentMethod;

use Spatie\LaravelData\Data;

class CustomerInlineData extends Data
{
    public ?string $reference_id;
    /** 'INDIVIDUAL' atau 'BUSINESS' (ikuti kebutuhanmu) */
    public ?string $type;
    public ?IndividualDetailData $individual_detail;

    public static function rules(): array
    {
        return [
            'reference_id'                 => ['nullable', 'string', 'max:191'],
            'type'                         => ['nullable', 'string', 'in:INDIVIDUAL,BUSINESS'],
            'individual_detail'            => ['nullable', 'array'],
            'individual_detail.given_names' => ['nullable', 'string', 'max:191'],
            'individual_detail.surname'    => ['nullable', 'string', 'max:191'],
        ];
    }
}
