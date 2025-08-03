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
            $table->foreignId('waste_type_id')->constrained('waste_types')->onDelete('cascade');
            $table->date('date');                  // tanggal pickup
            $table->string('time_slot');           // contoh: "08:00 - 10:00"
            $table->string('location')->nullable(); // opsional jika perlu lokasi spesifik
            $table->timestamps();
            $table->softDeletes();
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
