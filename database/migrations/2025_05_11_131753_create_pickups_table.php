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
        Schema::create('pickups', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('pickup_schedule_id');
            $t->unsignedBigInteger('customer_id');
            $t->unsignedBigInteger('petugas_id')->nullable();
            $t->enum('status', ['scheduled', 'assigned', 'completed', 'canceled'])->default('scheduled');
            $t->text('note')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->foreign('pickup_schedule_id')->references('id')->on('pickup_schedules')->cascadeOnDelete();
            $t->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('petugas_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickups');
    }
};
