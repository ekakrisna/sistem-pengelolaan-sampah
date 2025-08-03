<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Enum;
use App\Enums\UserEnum;
use App\Models\City;
use App\Models\District;
use App\Models\Pickup;
use App\Models\Province;
use App\Models\Village;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Attributes\Validation\Email;

class UserData extends Data
{
    public ?int $id;
    #[Max(191)]
    public string $name;
    #[Max(191), Unique('users', 'email'), Email]
    public string $email;
    #[Max(191)]
    public ?string $phone;
    #[Date]
    public ?Carbon $email_verified_at;
    #[Max(191)]
    public ?string $password;
    #[Enum(UserEnum::class)]
    public UserEnum $role;
    public ?int $province_id;
    public ?int $city_id;
    public ?int $district_id;
    public ?int $village_id;

    public ?string $address_detail;
    #[Max(100)]
    public ?string $remember_token;
    public ?Carbon $created_at;
    public ?Carbon $updated_at;

    public ?Province $province;
    public ?City $city;
    public ?District $district;
    public ?Village $village;

    public ?Collection $customerPickups;
    public ?Collection $petugasPickups;
}
