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
        // การลงเวลาเข้างานรายวัน
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date')->comment('วันที่ลงเวลา');
            $table->time('check_in_time')->comment('เวลาเข้างาน');
            $table->string('status')->default('present')->comment('สถานะ: present=ปกติ, late=สาย');
            $table->timestamps();

            // ผู้ใช้ลงเวลาได้วันละครั้ง
            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
