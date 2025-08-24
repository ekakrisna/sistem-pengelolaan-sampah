<?php

namespace Database\Factories;

use App\Enums\StatusPaymentEnum;
use App\Enums\Xendit\Common\ChannelCode;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'customer_id' => User::where('role', 'customer')->inRandomOrder()->first()->id ?? 1,
            'transaction_id' => Transaction::inRandomOrder()->first()->id ?? 1,
            'amount' => $this->faker->randomFloat(2, 5000, 100000),
            'status' => $this->faker->randomElement(StatusPaymentEnum::values()),
            'payment_method' => $this->faker->randomElement(ChannelCode::values()),
            'external_id' => 'trx-' . now()->format('YmdHis') . '-' . Str::random(6),
            'invoice_url' => $this->faker->url(),
            'xendit_data' => [
                'qr_string' => Str::random(20),
                'expires_at' => now()->addMinutes(30)->toISOString(),
            ],
            'paid_at' => $this->faker->boolean ? now() : null,
        ];
    }
}
