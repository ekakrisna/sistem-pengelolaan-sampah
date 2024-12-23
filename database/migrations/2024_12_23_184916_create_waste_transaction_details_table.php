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
        Schema::create('waste_transaction_details', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->unsignedBigInteger('users_id')->index('fk_waste_transaction_details_users1_idx');
            $table->bigInteger('waste_transactions_id')->index('fk_waste_transaction_details_waste_transactions1_idx');
            $table->bigInteger('payment_methods_id')->index('fk_waste_transaction_details_payment_methods1_idx');
            $table->decimal('total_price', 10);
            $table->enum('status', ['pending', 'completed', 'failed'])->nullable()->default('pending');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waste_transaction_details');
    }
};
