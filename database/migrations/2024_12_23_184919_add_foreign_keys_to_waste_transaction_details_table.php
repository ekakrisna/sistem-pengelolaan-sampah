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
        Schema::table('waste_transaction_details', function (Blueprint $table) {
            $table->foreign(['payment_methods_id'], 'fk_waste_transaction_details_payment_methods1')->references(['id'])->on('payment_methods')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['users_id'], 'fk_waste_transaction_details_users1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['waste_transactions_id'], 'fk_waste_transaction_details_waste_transactions1')->references(['id'])->on('waste_transactions')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waste_transaction_details', function (Blueprint $table) {
            $table->dropForeign('fk_waste_transaction_details_payment_methods1');
            $table->dropForeign('fk_waste_transaction_details_users1');
            $table->dropForeign('fk_waste_transaction_details_waste_transactions1');
        });
    }
};
