<?php

use App\Enums\PickupScheduleEnum;
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
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('waste_type_id');
            $table->enum('day_of_week', PickupScheduleEnum::values());
            $table->time('start_pickup_time');
            $table->time('end_pickup_time');
            $table->char('village_code', 10);
            $table->unsignedBigInteger('quota')->default(0);

            $table->date('pickup_date')->nullable();
            $table->datetime('scheduled_for')->nullable();
            $table->datetime('run_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('waste_type_id')->references('id')->on('waste_types')->cascadeOnDelete();
            $table->foreign('village_code')->references('code')->on('villages')->cascadeOnDelete();
            $table->index(['village_code', 'waste_type_id', 'day_of_week']);
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
