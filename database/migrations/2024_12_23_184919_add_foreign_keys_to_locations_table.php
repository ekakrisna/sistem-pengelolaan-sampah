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
        Schema::table('locations', function (Blueprint $table) {
            $table->foreign(['users_id'], 'fk_locations_users')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['villages_id'], 'fk_locations_villages1')->references(['id'])->on('villages')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign('fk_locations_users');
            $table->dropForeign('fk_locations_villages1');
        });
    }
};
