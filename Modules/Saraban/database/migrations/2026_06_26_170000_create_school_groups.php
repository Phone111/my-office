<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * กลุ่มโรงเรียน (กลุ่มสถานศึกษา) — AMSS ส่วน 16: ส่งหนังสือถึงทั้งกลุ่มทีเดียว
 * เช่น "โรงเรียนในฝัน" — โรงเรียน 1 แห่งอยู่ได้หลายกลุ่ม (many-to-many)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete(); // เขตเจ้าของกลุ่ม
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('school_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_group_id')->constrained('school_groups')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete(); // โรงเรียนสมาชิก
            $table->unique(['school_group_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_group_members');
        Schema::dropIfExists('school_groups');
    }
};
