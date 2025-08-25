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
        Schema::create('pickup_fees', function (Blueprint $table) {
            $table->id();
            $table->char('village_code', 10);
            $table->unsignedBigInteger('waste_type_id');
            $table->unsignedBigInteger('admin_id');
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('village_code')->references('code')->on('villages')->cascadeOnDelete();
            $table->foreign('waste_type_id')->references('id')->on('waste_types')->cascadeOnDelete();
            $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['village_code', 'waste_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_fees');
    }
};
