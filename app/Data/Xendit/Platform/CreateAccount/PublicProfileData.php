<?php

namespace App\Data\Xendit\Platform\CreateAccount;

use Spatie\LaravelData\Data;

final class PublicProfileData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $business_name = null,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'business_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'business_name' => $this->business_name,
        ];
    }

    public function toPayload(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'business_name' => $this->business_name,
        ];
    }
}
