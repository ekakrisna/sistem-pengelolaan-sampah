<?php
// app/Data/PaymentMethod/BillingInformationData.php
namespace App\Data\PaymentMethod;

use Spatie\LaravelData\Data;

class BillingInformationData extends Data
{
    public ?string $city;
    public ?string $country;       // ISO2 (ID/PH/…)
    public ?string $postal_code;
    public ?string $street_line1;
    public ?string $street_line2;
    public ?string $province_state;

    public static function rules(): array
    {
        return [
            'city'           => ['nullable', 'string', 'max:255'],
            'country'        => ['nullable', 'string', 'size:2'],
            'postal_code'    => ['nullable', 'string', 'max:50'],
            'street_line1'   => ['nullable', 'string', 'max:255'],
            'street_line2'   => ['nullable', 'string', 'max:255'],
            'province_state' => ['nullable', 'string', 'max:255'],
        ];
    }
}
