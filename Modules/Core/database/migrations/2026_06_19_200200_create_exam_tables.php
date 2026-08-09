<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // คลังข้อสอบ
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->string('subject');           // กลุ่มสาระ
            $table->string('grade');             // ชั้น
            $table->string('standard')->nullable();   // มาตรฐาน
            $table->string('indicator')->nullable();  // ตัวชี้วัด
            $table->text('question');
            $table->json('options')->nullable(); // ตัวเลือก (ปรนัย)
            $table->tinyInteger('answer')->nullable(); // index ตัวเลือกที่ถูก
            $table->unsignedInteger('score')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // แบบทดสอบ (ต้นฉบับ)
        Schema::create('exam_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->string('grade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('exam_test_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_test_id')->constrained('exam_tests')->cascadeOnDelete();
            $table->foreignId('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
        });

        // รายการสอบ (บริหารการสอบ)
        Schema::create('exam_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_test_id')->nullable()->constrained('exam_tests')->nullOnDelete();
            $table->string('name');
            $table->smallInteger('academic_year')->nullable();
            $table->string('round')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ผลการสอบรายโรงเรียน (ป้อนผลกระดาษ)
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_run_id')->constrained('exam_runs')->cascadeOnDelete();
            $table->foreignId('school_unit_id')->constrained('units')->cascadeOnDelete();
            $table->unsignedInteger('students')->default(0);    // จำนวนผู้เข้าสอบ
            $table->unsignedInteger('passed')->default(0);      // ผ่านเกณฑ์ (>=50%)
            $table->decimal('avg_percent', 5, 2)->nullable();   // คะแนนเฉลี่ย %
            $table->string('note')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['exam_run_id', 'school_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('exam_runs');
        Schema::dropIfExists('exam_test_question');
        Schema::dropIfExists('exam_tests');
        Schema::dropIfExists('exam_questions');
    }
};
