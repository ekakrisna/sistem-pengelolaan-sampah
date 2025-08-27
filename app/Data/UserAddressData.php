<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class UserAddressData extends Data
{
    public function __construct(

        public ?int $id,
        public int $user_id,
        #[Max(10)]
        public string $province_code,
        #[Max(10)]
        public string $city_code,
        #[Max(10)]
        public string $district_code,
        #[Max(10)]
        public string $village_code,
        #[Max(191)]
        public ?string $label,
        #[Max(191)]
        public ?string $address_detail,
        #[Numeric]
        public ?int $lat,
        #[Numeric]
        public ?int $lng,

        public int $is_default,
        public ?string $meta,
        #[Date]
        public ?Carbon $deleted_at,

        public ?UserData $user,
        public ?ProvinceData $province,
        public ?CityData $city,
        public ?DistrictData $district,
        public ?VillageData $village,

        #[DataCollectionOf(TransactionItemData::class)]
        public ?DataCollection $transaction_items,
    ) {}
}
