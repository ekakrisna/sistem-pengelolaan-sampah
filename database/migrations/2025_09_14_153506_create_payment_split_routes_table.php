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
        Schema::create('payment_split_routes', function (Blueprint $t) {
            $t->id();

            $t->unsignedBigInteger('payment_id');       // link ke payments
            $t->unsignedBigInteger('transaction_id');   // redundant utk query cepat
            $t->unsignedBigInteger('admin_id')->nullable(); // merchant/owner dari rute

            $t->string('currency', 8)->default('IDR');
            $t->unsignedBigInteger('flat_amount')->nullable();     // dalam minor unit (IDR -> rupiah)
            $t->decimal('percent_amount', 8, 2)->nullable();       // kalau pakai persentase

            $t->string('destination_account_id'); // xendit_for_user_id
            $t->string('reference_id')->unique(); // per-route reference untuk audit

            $t->string('split_rule_id')->nullable()->index();  // id split rule dipakai payment ini
            $t->enum('status', ['planned', 'applied', 'settled', 'failed'])->default('planned');

            $t->timestamp('applied_at')->nullable();  // saat payment request dibuat sukses
            $t->timestamp('settled_at')->nullable();  // kalau kamu punya sinyal settlement (opsional)
            $t->json('meta')->nullable();             // bebas: hash penghitungan, catatan, dll

            $t->timestamps();

            $t->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
            $t->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
            $t->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            $t->index(['payment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_split_routes');
    }
};
