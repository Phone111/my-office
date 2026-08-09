<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * แผนพัฒนาตนเอง / ID Plan ของครูและบุคลากร
     * แนบไฟล์ + เขียนเป้าหมายการประเมินได้ แยกตามปีการศึกษา
     */
    public function up(): void
    {
        Schema::create('development_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('academic_year')->comment('ปีการศึกษา เช่น 2569');
            $table->text('goals')->comment('เป้าหมายการประเมิน/พัฒนาตนเอง');
            $table->string('file_path')->nullable()->comment('ไฟล์แนบประกอบแผน');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('development_plans');
    }
};
