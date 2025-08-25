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
        Schema::create('user_addresses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->char('province_code', 10);
            $t->char('city_code', 10);
            $t->char('district_code', 10);
            $t->char('village_code', 10);
            $t->string('label')->nullable();
            $t->string('address_detail')->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->boolean('is_default')->default(false);
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('province_code')->references('code')->on('provinces');
            $t->foreign('city_code')->references('code')->on('cities');
            $t->foreign('district_code')->references('code')->on('districts');
            $t->foreign('village_code')->references('code')->on('villages');
            $t->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
