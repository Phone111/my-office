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
        // ตำแหน่งของบุคลากร
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อตำแหน่ง');
            $table->string('code')->nullable()->unique()->comment('รหัสตำแหน่ง');
            $table->unsignedInteger('level')->default(0)->comment('ระดับ/ลำดับความอาวุโส');
            $table->boolean('is_active')->default(true)->comment('สถานะการใช้งาน');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
