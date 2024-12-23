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
        Schema::create('waste_managements', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->unsignedBigInteger('users_id')->index('fk_waste_managements_users1_idx');
            $table->bigInteger('packages_id')->index('fk_waste_managements_packages1_idx');
            $table->unsignedBigInteger('locations_id')->index('fk_waste_managements_locations1_idx');
            $table->enum('pickup_schedule', ['daily', 'weekly'])->nullable();
            $table->time('pickup_time');
            $table->tinyInteger('status')->default(0);
            $table->dateTime('expired_at');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waste_managements');
    }
};
