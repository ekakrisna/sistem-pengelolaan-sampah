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
        Schema::create('pickup_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('waste_type_id');
            $table->unsignedBigInteger('admin_id');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_pickup_time');
            $table->time('end_pickup_time');
            $table->char('code_village_pickup', 10);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('waste_type_id')->references('id')->on('waste_types')->onDelete('cascade');
            $table->foreign('code_village_pickup')->references('code')->on('villages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_schedules');
    }
};
