<?php

namespace App\Data\Xendit\Platform\CreateAccount;

use App\Data\Xendit\Platform\CreateAccount\Configurations;
use App\Data\Xendit\Platform\CreateAccount\PublicProfileData;
use App\Enums\Xendit\Platform\CreateCustomer\Type;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

final class CreateAccountRequestData extends Data
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $email,
        public ?Type $type,
        public PublicProfileData $public_profile,
        public ?Configurations $configurations = null,
    ) {}

    public static function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'type'  => ['nullable', Rule::in(Type::values())],
            'public_profile' => ['required', 'array'],
            'public_profile.business_name' => ['required', 'string', 'max:255'],
            'configurations' => ['nullable', 'array'],
        ];
    }

    public function toPayload(): array
    {
        $payload = [
            'email'          => $this->email,
            'public_profile' => $this->public_profile->toArray(),
        ];

        if (!empty($this->type)) {
            $payload['type'] = $this->type; // OWNED / MANAGED
        } elseif ($this->configurations) {
            $cfg = $this->configurations->toPayload();
            if (!empty($cfg)) {
                $payload['configurations'] = $cfg; // CUSTOM (tanpa 'type')
            }
        }

        // Hapus key kosong/null, supaya 'configurations' {} tidak terkirim
        return array_filter($payload, static fn($v) => $v !== null && $v !== [] && $v !== '');
    }

    public static function afterValidation(Validator $validator, self $dto): void
    {
        $hasType = !empty($dto->type);
        $hasCfg  = !empty($dto->configurations) && !empty($dto->configurations->toPayload());

        if ($hasType && $hasCfg) {
            $validator->errors()->add('type', 'Provide either "type" OR "configurations", not both.');
        }
        if (!$hasType && !$hasCfg) {
            $validator->errors()->add('type', 'You must provide either "type" or "configurations".');
        }
    }
}
