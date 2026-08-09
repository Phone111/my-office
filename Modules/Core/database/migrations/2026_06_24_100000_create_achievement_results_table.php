<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ผลสัมฤทธิ์ระดับชาติ (O-NET / NT / RT) — คะแนนเฉลี่ยรายโรงเรียน ต่อปี/ชั้น/วิชา
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievement_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('test_type', 10);          // onet / nt / rt
            $table->unsignedSmallInteger('academic_year'); // ปีการศึกษา (พ.ศ.)
            $table->string('grade', 10);              // ป.6 / ม.3 / ม.6 / ป.3 / ป.1
            $table->string('subject', 30);            // คีย์วิชา/สมรรถนะ
            $table->decimal('score', 5, 2)->nullable(); // คะแนนเฉลี่ย 0-100
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['unit_id', 'test_type', 'academic_year', 'grade', 'subject'], 'achievement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_results');
    }
};
