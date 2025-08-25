<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Payment>
 */
final class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::inRandomOrder()->first()->id,
            'customer_id' => User::whereNotIn('role', ['admin', 'petugas', 'super_admin'])->inRandomOrder()->first()->id,
            'amount' => fake()->randomFloat(2, 0, 9999999999),
            'currency' => fake()->currencyCode,
            'status' => fake()->randomElement(['initiated', 'awaiting_payment', 'succeeded', 'failed', 'expired', 'canceled', 'refund_pending', 'refunded']),
            'channel' => fake()->optional()->word,
            'method_code' => fake()->optional()->word,
            'reference_id' => fake()->optional()->word,
            'idempotency_key' => fake()->optional()->word,
            'xendit_account_id' => fake()->optional()->word,
            'xendit_payment_request_id' => fake()->optional()->word,
            'xendit_charge_id' => fake()->optional()->word,
            'xendit_invoice_id' => fake()->optional()->word,
            'va_numbers' => fake()->optional()->word,
            'qris_qr_string' => fake()->optional()->text,
            'checkout_url' => fake()->optional()->word,
            'ewallet_info' => fake()->optional()->word,
            'expires_at' => fake()->optional()->datetime(),
            'paid_at' => fake()->optional()->datetime(),
            'failure_code' => fake()->optional()->word,
            'failure_message' => fake()->optional()->word,
            'xendit_data' => fake()->optional()->word,
        ];
    }
}
