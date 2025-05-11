<?php

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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');

            $table->decimal('amount', 10, 2); // contoh: 25000.00
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('payment_method')->nullable();   // contoh: qris, transfer
            $table->string('proof_image')->nullable();      // path bukti pembayaran

            $table->timestamp('paid_at')->nullable();       // kapan dibayar
            $table->timestamps();
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
