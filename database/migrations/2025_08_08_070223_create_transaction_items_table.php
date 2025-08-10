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
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('pickup_fee_id')->nullable();

            $table->string('description')->nullable();
            $table->decimal('unit_amount', 10, 2);
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('line_total', 12, 2);

            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('transaction_id')
                ->references('id')->on('transactions')->onDelete('cascade');

            $table->foreign('pickup_fee_id')
                ->references('id')->on('pickup_fees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
