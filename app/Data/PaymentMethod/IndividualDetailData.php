<?php
// app/Data/PaymentMethod/IndividualDetailData.php
namespace App\Data\PaymentMethod;

use Spatie\LaravelData\Data;

class IndividualDetailData extends Data
{
    public ?string $given_names;
    public ?string $surname;

    public static function rules(): array
    {
        return [
            'given_names' => ['nullable', 'string', 'max:191'],
            'surname'     => ['nullable', 'string', 'max:191'],
        ];
    }
}
