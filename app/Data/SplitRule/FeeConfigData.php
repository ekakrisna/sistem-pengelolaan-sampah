<?php

namespace App\Data\SplitRule;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class FeeConfigData extends Data
{
    public function __construct(
        public string $name,
        public string $description,

        #[DataCollectionOf(RouteData::class)]
        public DataCollection $routes,
    ) {}

    public function toArray(): array
    {
        $arr = parent::toArray();

        // Hapus null value
        $arr['routes'] = collect($arr['routes'])
            ->map(fn($r) => array_filter($r, fn($v) => !is_null($v) && $v !== ''))
            ->all();

        return array_filter($arr, fn($v) => !is_null($v) && $v !== '');
    }
}
