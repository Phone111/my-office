<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ปฏิทินปฏิบัติงานของผู้บริหาร (เลขาฯ บันทึก, ผอ. ดูอย่างเดียว)
     */
    public function up(): void
    {
        Schema::create('executive_events', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('หัวข้อ/วาระ');
            $table->text('description')->nullable()->comment('รายละเอียด');
            $table->string('location')->nullable()->comment('สถานที่');
            $table->dateTime('start_at')->comment('เริ่ม');
            $table->dateTime('end_at')->nullable()->comment('สิ้นสุด');
            $table->boolean('all_day')->default(false)->comment('ทั้งวัน');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('start_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_events');
    }
};
