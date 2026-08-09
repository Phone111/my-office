<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ว.PA — ข้อตกลงในการพัฒนางาน (ว9/2564)
 * ส่วนที่ 1: ข้อตกลงพัฒนางานตามมาตรฐานตำแหน่ง (pa_tasks)
 * ส่วนที่ 2: ประเด็นท้าทาย (ฟิลด์ใน pa_agreements)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pa_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year');          // ปีงบประมาณ (พ.ศ.)
            $table->string('position_type', 30)->default('teacher');

            // ส่วนที่ 2 ประเด็นท้าทาย
            $table->string('challenge_issue', 255)->nullable();   // ประเด็นท้าทาย
            $table->text('challenge_problem')->nullable();        // สภาพปัญหา
            $table->text('challenge_method')->nullable();         // วิธีการดำเนินการ
            $table->text('challenge_outcome_quant')->nullable();  // ผลลัพธ์เชิงปริมาณ
            $table->text('challenge_outcome_qual')->nullable();   // ผลลัพธ์เชิงคุณภาพ

            // สถานะ/เห็นชอบ/ประเมิน
            $table->string('status', 20)->default('draft');       // draft/submitted/approved/evaluated
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approver_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();           // คะแนนประเมินปลายปี (0-100)
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'fiscal_year']);
        });

        Schema::create('pa_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('pa_agreements')->cascadeOnDelete();
            $table->unsignedTinyInteger('aspect');                // 1/2/3 (ด้านตามมาตรฐานตำแหน่ง)
            $table->text('task');                                 // งาน/ลักษณะงานที่ปฏิบัติ
            $table->text('expected_outcome')->nullable();         // ผลลัพธ์ที่คาดหวัง
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pa_tasks');
        Schema::dropIfExists('pa_agreements');
    }
};
