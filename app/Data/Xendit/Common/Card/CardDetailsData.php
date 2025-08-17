<?php

namespace App\Data\Xendit\Common\Card;

use App\Data\Casts\DigitsOnlyCast;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

class CardDetailsData extends Data
{
    public function __construct(
        #[WithCast(DigitsOnlyCast::class)]
        public string $cvn,

        #[WithCast(DigitsOnlyCast::class)]
        public string $card_number,

        #[WithCast(DigitsOnlyCast::class)]
        public string $expiry_year,   // YYYY

        #[WithCast(DigitsOnlyCast::class)]
        public string $expiry_month,  // MM

        public ?string $cardholder_first_name = null,
        public ?string $cardholder_last_name = null,
        public ?string $cardholder_email = null,
        public ?string $cardholder_phone_number = null,
    ) {}

    public static function rules(): array
    {
        // tahun & bulan sekarang untuk rule kedaluwarsa
        $yNow = (int) date('Y');
        $mNow = (int) date('m');

        return [
            'cvn'                     => ['required', 'regex:/^\d{3,4}$/'],
            'card_number'             => ['required', 'regex:/^\d{12,19}$/', function ($attr, $value, $fail) {
                if (!self::passesLuhn((string) $value)) {
                    $fail('Card number is invalid (Luhn check failed).');
                }
            }],
            'expiry_year'             => ['required', 'digits:4', "integer", "min:$yNow", "max:" . ($yNow + 25)],
            'expiry_month'            => ['required', 'regex:/^(0[1-9]|1[0-2])$/', function ($attr, $value, $fail) use ($yNow, $mNow) {
                // cek kadaluarsa: kalau tahun sama & bulan < bulan ini -> fail
                $reqYear  = request('channel_properties.card_details.expiry_year') ?? request('expiry_year');
                $reqMonth = (int) $value;
                $reqYearI = (int) $reqYear;
                if ($reqYearI === $yNow && $reqMonth < $mNow) {
                    $fail('Card is expired.');
                }
            }],

            'cardholder_first_name'   => ['nullable', 'string', 'max:100'],
            'cardholder_last_name'    => ['nullable', 'string', 'max:100'],
            'cardholder_email'        => ['nullable', 'email', 'max:255'],
            'cardholder_phone_number' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function toArray(): array
    {
        // pastikan expiry_month dipad 2 digit
        $mm = str_pad($this->expiry_month, 2, '0', STR_PAD_LEFT);

        return array_filter([
            'cvn'                     => $this->cvn,
            'card_number'             => $this->card_number,
            'expiry_year'             => $this->expiry_year,
            'expiry_month'            => $mm,
            'cardholder_first_name'   => $this->cardholder_first_name,
            'cardholder_last_name'    => $this->cardholder_last_name,
            'cardholder_email'        => $this->cardholder_email,
            'cardholder_phone_number' => $this->cardholder_phone_number,
        ], static fn($v) => $v !== null && $v !== '');
    }

    /**
     * Masked view untuk logging/debug (jangan log cvn!)
     */
    public function masked(): array
    {
        $last4 = substr($this->card_number, -4);
        return [
            'card_number'  => '************' . $last4,
            'expiry'       => str_pad($this->expiry_month, 2, '0', STR_PAD_LEFT) . '/' . $this->expiry_year,
            'cardholder'   => trim(($this->cardholder_first_name ?? '') . ' ' . ($this->cardholder_last_name ?? '')) ?: null,
            'email'        => $this->cardholder_email,
            'phone_number' => $this->cardholder_phone_number,
        ];
    }

    private static function passesLuhn(string $number): bool
    {
        $sum = 0;
        $alt = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = (int) $number[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) $n -= 9;
            }
            $sum += $n;
            $alt = !$alt;
        }
        return $sum % 10 === 0;
    }
}
