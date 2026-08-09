<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * บันทึกปฏิบัติงานผู้บริหาร — สมุดบันทึกการปฏิบัติงานรายวันของแต่ละคน
     */
    public function up(): void
    {
        Schema::create('executive_work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('log_date')->comment('วันที่ปฏิบัติงาน');
            $table->string('title')->comment('หัวข้องาน/ภารกิจ');
            $table->text('detail')->nullable()->comment('รายละเอียด');
            $table->string('location')->nullable()->comment('สถานที่');
            $table->timestamps();

            $table->index(['user_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_work_logs');
    }
};
