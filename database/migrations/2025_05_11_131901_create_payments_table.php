<?php

use App\Enums\StatusPaymentEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('transaction_id');
            $t->unsignedBigInteger('customer_id');

            $t->decimal('amount', 12, 2);
            $t->string('currency', 8)->default('IDR');

            $t->enum('status', StatusPaymentEnum::values())
                ->default(StatusPaymentEnum::INITIATED->value);

            $t->string('channel')->nullable();       // qris, va_bca, ovo, gopay, dll
            $t->string('method_code')->nullable();   // opsional, alias lama "payment_method"
            $t->string('reference_id')->nullable()->index();  // external/reference app
            $t->string('idempotency_key')->nullable()->unique();
            $t->string('xendit_account_id')->nullable()->index();

            // ID kemungkinan dari berbagai produk Xendit:
            $t->string('xendit_payment_request_id')->nullable()->unique();
            $t->string('xendit_charge_id')->nullable()->unique();
            $t->string('xendit_invoice_id')->nullable()->unique();

            // Data spesifik channel
            $t->json('va_numbers')->nullable();
            $t->text('qris_qr_string')->nullable();
            $t->string('checkout_url')->nullable();
            $t->json('ewallet_info')->nullable();

            $t->timestamp('expires_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->string('failure_code')->nullable();
            $t->string('failure_message')->nullable();
            $t->json('xendit_data')->nullable();     // raw payload

            $t->timestamps();
            $t->softDeletes();

            $t->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
            $t->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
            $t->index(['transaction_id', 'status', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
