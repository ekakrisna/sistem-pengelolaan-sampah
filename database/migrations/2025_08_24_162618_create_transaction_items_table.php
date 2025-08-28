<?php

use App\Enums\TransactionItemTypeEnum;
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
        Schema::create('transaction_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('transaction_id');

            $t->enum('item_type', TransactionItemTypeEnum::values())
                ->default(TransactionItemTypeEnum::PICKUP->value);

            // Baris pickup mengikat 3 hal berikut:
            $t->unsignedBigInteger('user_address_id')->nullable();
            $t->unsignedBigInteger('pickup_schedule_id')->nullable();
            $t->unsignedBigInteger('pickup_fee_id')->nullable();

            // Setelah bayar, kita materialize menjadi pickup nyata & isi kolom ini:
            $t->unsignedBigInteger('pickup_id')->nullable();

            $t->string('description')->nullable();
            $t->decimal('unit_amount', 12, 2);
            $t->unsignedInteger('qty')->default(1);
            $t->decimal('line_total', 12, 2);
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
            $t->foreign('user_address_id')->references('id')->on('user_addresses')->nullOnDelete();
            $t->foreign('pickup_schedule_id')->references('id')->on('pickup_schedules')->nullOnDelete();
            $t->foreign('pickup_fee_id')->references('id')->on('pickup_fees')->nullOnDelete();
            $t->foreign('pickup_id')->references('id')->on('pickups')->nullOnDelete();

            // Hindari duplikasi baris untuk kombinasi lokasi + jadwal (kalau qty=1)
            $t->index(['transaction_id', 'user_address_id', 'pickup_schedule_id'], 'txn_items_tx_addr_sched_idx');
            $t->index(['transaction_id', 'item_type']);
            $t->unique(
                ['transaction_id', 'user_address_id', 'pickup_schedule_id', 'pickup_fee_id'],
                'ti_tx_addr_sched_fee_uq'
            );
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
