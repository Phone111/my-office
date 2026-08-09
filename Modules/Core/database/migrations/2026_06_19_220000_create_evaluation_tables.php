<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // รอบการประเมิน (ปีงบประมาณ / ครั้งที่)
        Schema::create('evaluation_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->smallInteger('fiscal_year')->nullable();
            $table->tinyInteger('period')->nullable(); // ครั้งที่ 1/2
            $table->boolean('is_current')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // องค์ประกอบ/เกณฑ์การประเมิน
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('max_score', 6, 2)->default(100);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // การประเมินรายคน
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->nullable()->constrained('evaluation_rounds')->nullOnDelete();
            $table->foreignId('evaluee_id')->constrained('users')->cascadeOnDelete();   // ผู้ถูกประเมิน
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete(); // ผู้ประเมิน
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('total_score', 6, 2)->nullable();
            $table->decimal('percent', 5, 2)->nullable();
            $table->string('grade')->nullable();
            $table->text('strengths')->nullable();       // จุดเด่น
            $table->text('improvements')->nullable();    // สิ่งที่ควรพัฒนา
            $table->text('evaluator_comment')->nullable();
            $table->text('evaluee_note')->nullable();    // ความเห็นผู้รับการประเมิน
            $table->string('status')->default('draft');  // draft | evaluated | acknowledged
            $table->dateTime('acknowledged_at')->nullable();
            $table->timestamps();
        });

        // คะแนนรายองค์ประกอบ
        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('evaluation_criteria')->cascadeOnDelete();
            $table->decimal('score', 6, 2)->nullable();
            $table->timestamps();
            $table->unique(['evaluation_id', 'criteria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('evaluation_criteria');
        Schema::dropIfExists('evaluation_rounds');
    }
};
