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
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('email');
                $table->enum('role', ['admin', 'petugas', 'customer'])->default('customer')->after('password');

                // Relasi ke wilayah
                $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
                $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
                $table->foreignId('village_id')->nullable()->constrained('villages')->nullOnDelete();

                $table->text('address_detail')->nullable()->after('village_id');
            });
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
