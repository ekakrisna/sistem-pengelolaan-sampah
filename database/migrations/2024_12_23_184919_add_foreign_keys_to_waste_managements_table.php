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
        Schema::table('waste_managements', function (Blueprint $table) {
            $table->foreign(['locations_id'], 'fk_waste_managements_locations1')->references(['id'])->on('locations')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['packages_id'], 'fk_waste_managements_packages1')->references(['id'])->on('packages')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['users_id'], 'fk_waste_managements_users1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waste_managements', function (Blueprint $table) {
            $table->dropForeign('fk_waste_managements_locations1');
            $table->dropForeign('fk_waste_managements_packages1');
            $table->dropForeign('fk_waste_managements_users1');
        });
    }
};
