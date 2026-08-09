<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ตามแบบฟอร์มเดิม:
     * - รางวัล: ไฟล์หลักฐาน
     * - การอบรม: กลุ่มสาระ, งบประมาณจาก, ไฟล์หลักฐานอ้างอิง
     */
    public function up(): void
    {
        Schema::table('staff_awards', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('note')->comment('ไฟล์หลักฐาน');
        });

        Schema::table('staff_trainings', function (Blueprint $table) {
            $table->string('subject_group')->nullable()->after('course_name')->comment('กลุ่มสาระ');
            $table->string('budget_source')->nullable()->after('hours')->comment('งบประมาณจาก');
            $table->string('file_path')->nullable()->after('note')->comment('ไฟล์หลักฐานอ้างอิง');
        });
    }

    public function down(): void
    {
        Schema::table('staff_awards', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
        Schema::table('staff_trainings', function (Blueprint $table) {
            $table->dropColumn(['subject_group', 'budget_source', 'file_path']);
        });
    }
};
