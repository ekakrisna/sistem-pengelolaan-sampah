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
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreign(['locations_id'], 'fk_schedules_locations1')->references(['id'])->on('locations')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['colectors_id'], 'fk_schedules_users1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['waste_categories_id'], 'fk_schedules_waste_categories')->references(['id'])->on('waste_categories')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign('fk_schedules_locations1');
            $table->dropForeign('fk_schedules_users1');
            $table->dropForeign('fk_schedules_waste_categories');
        });
    }
};
