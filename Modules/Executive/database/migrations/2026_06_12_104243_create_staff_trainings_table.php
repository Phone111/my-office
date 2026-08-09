<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สรุปการอบรม/พัฒนาของบุคลากร
     */
    public function up(): void
    {
        Schema::create('staff_trainings', function (Blueprint $table) {
            $table->id();
            $table->string('staff_name')->comment('ชื่อบุคลากร');
            $table->string('course_name')->comment('หลักสูตร/หัวข้ออบรม');
            $table->string('organizer')->nullable()->comment('หน่วยงานที่จัด');
            $table->date('start_date')->comment('วันเริ่ม');
            $table->date('end_date')->nullable()->comment('วันสิ้นสุด');
            $table->unsignedInteger('hours')->default(0)->comment('จำนวนชั่วโมง');
            $table->string('location')->nullable()->comment('สถานที่');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_trainings');
    }
};
