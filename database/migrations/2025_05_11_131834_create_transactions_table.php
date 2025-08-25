<?php

use App\Enums\StatusTransactionEnum;
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
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id');
            $t->string('number')->nullable()->unique();     // INV-2025-000123
            $t->enum('status', StatusTransactionEnum::values())
                ->default(StatusTransactionEnum::DRAFT->value);
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('discount_amount', 12, 2)->default(0);
            $t->decimal('tax_amount', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->string('currency', 8)->default('IDR');
            $t->timestamp('due_at')->nullable();
            $t->timestamp('expires_at')->nullable();        // sinkron dgn payment expiry
            $t->text('description')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
            $t->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
