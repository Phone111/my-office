<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // มาตรฐานการจัดการศึกษา
        Schema::create('supervision_standards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ตัวชี้วัด (ภายใต้แต่ละมาตรฐาน)
        Schema::create('supervision_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_id')->constrained('supervision_standards')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('weight')->default(1); // น้ำหนัก
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // รอบการนิเทศ (รายภาคเรียน)
        Schema::create('supervision_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // เช่น ภาคเรียนที่ 1 ปีการศึกษา 2569 ครั้งที่ 1
            $table->smallInteger('academic_year')->nullable(); // ปีการศึกษา (พ.ศ.)
            $table->tinyInteger('semester')->nullable();  // ภาคเรียน 1/2
            $table->boolean('is_current')->default(false); // รอบที่ใช้งานปัจจุบัน
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervision_indicators');
        Schema::dropIfExists('supervision_standards');
        Schema::dropIfExists('supervision_rounds');
    }
};
