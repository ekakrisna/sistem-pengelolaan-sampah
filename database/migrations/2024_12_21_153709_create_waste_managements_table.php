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
            $table->bigInteger('users_id');
            $table->bigInteger('waste_categories_id');
            $table->bigInteger('locations_id');
            $table->bigInteger('packages_id');
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
