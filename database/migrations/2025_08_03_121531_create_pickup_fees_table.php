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
        Schema::create('pickup_fees', function (Blueprint $table) {
            $table->id();
            $table->char('village_code', 10);
            $table->unsignedBigInteger('waste_type_id');
            $table->unsignedBigInteger('admin_id');
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('village_code')->references('code')->on('villages')->onDelete('cascade');
            $table->foreign('waste_type_id')->references('id')->on('waste_types')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_fees');
    }
};
