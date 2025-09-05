<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Enum;
use App\Enums\UserEnum;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\DataCollection;

class UserData extends Data
{
    public function __construct(
        #[Unique('users', 'id')]
        public ?int $id,
        #[Max(191)]
        public string $name,
        #[Max(191)]
        public string $email,
        #[Max(191)]
        public ?string $phone,
        #[Date]
        public ?Carbon $email_verified_at,
        #[Hidden]
        public ?string $password,

        #[Enum(UserEnum::class)]
        public UserEnum $role,

        public ?string $xendit_for_user_id,

        #[Hidden]
        public ?string $remember_token,
        #[Date]
        public ?Carbon $created_at,
        #[Date]
        public ?Carbon $updated_at,
        #[Date]
        public ?Carbon $deleted_at,

        #[DataCollectionOf(PaymentData::class)]
        public ?DataCollection $payments,
        #[DataCollectionOf(UserAddressData::class)]
        public ?DataCollection $user_addresses,
    ) {}


    public static function paginatedResponse(LengthAwarePaginator $paginator): array
    {
        $items = method_exists($paginator, 'items') ? $paginator->items() : $paginator;
        return [
            'users' => self::collect($items),
            'pagination' => [
                'total'         => $paginator->total(),
                'per_page'      => $paginator->perPage(),
                'current_page'  => $paginator->currentPage(),
                'last_page'     => $paginator->lastPage(),
                'from'          => $paginator->firstItem(),
                'to'            => $paginator->lastItem(),
            ],
        ];
    }
}
