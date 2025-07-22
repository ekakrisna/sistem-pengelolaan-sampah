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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->enum('role', ['admin', 'petugas', 'customer'])->default('customer')->after('password');

            $table->char('province_id', 2)->nullable()->after('role');
            $table->char('city_id', 4)->nullable()->after('province_id');
            $table->char('district_id', 7)->nullable()->after('city_id');
            $table->char('village_id', 10)->nullable()->after('district_id');

            // Relasi ke wilayah
            $table->foreign('province_id')->references('code')->on('provinces')->nullOnDelete();
            $table->foreign('city_id')->references('code')->on('cities')->nullOnDelete();
            $table->foreign('district_id')->references('code')->on('districts')->nullOnDelete();
            $table->foreign('village_id')->references('code')->on('villages')->nullOnDelete();

            $table->text('address_detail')->nullable()->after('village_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'role',
                'province_id',
                'city_id',
                'district_id',
                'village_id',
                'address_detail'
            ]);
        });
    }
};
