<?php
// app/Data/PaymentMethod/CardInformationData.php
namespace App\Data\PaymentMethod;

use Spatie\LaravelData\Data;

/**
 * ⚠️ Gunakan HANYA jika kamu PCI DSS (raw PAN).
 * Jika tidak, biarkan null (linking via redirect/3DS).
 */
class CardInformationData extends Data
{
    public ?string $card_number;
    public ?string $expiry_month;   // MM
    public ?string $expiry_year;    // YY
    public ?string $cardholder_name;
    public ?string $cvv;

    public static function rules(): array
    {
        return [
            'card_number'     => ['nullable', 'string', 'min:12', 'max:19'],
            'expiry_month'    => ['nullable', 'string', 'regex:/^(0[1-9]|1[0-2])$/'], // 01–12
            'expiry_year'     => ['nullable', 'string', 'regex:/^\d{4}$/'],           // 4 digit (YYYY)
            'cardholder_name' => ['nullable', 'string', 'max:191'],
            'cvv'             => ['nullable', 'string', 'min:3', 'max:4'],
        ];
    }
}
