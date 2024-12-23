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
        Schema::create('schedules', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('waste_categories_id')->index('fk_schedules_waste_categories_idx');
            $table->unsignedBigInteger('locations_id')->index('fk_schedules_locations1_idx');
            $table->unsignedBigInteger('colectors_id')->index('fk_schedules_users1_idx');
            $table->date('pickup_date')->nullable();
            $table->date('pickup_time')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
