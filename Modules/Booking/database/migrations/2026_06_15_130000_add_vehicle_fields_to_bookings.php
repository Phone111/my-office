<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์รายละเอียดสำหรับ "บันทึกขอจองรถ" (ใช้เฉพาะการจองรถ — nullable ทั้งหมด)
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('division')->nullable()->after('purpose')->comment('ส่วนราชการ/กลุ่มงาน');
            $table->text('companions')->nullable()->after('division')->comment('ข้าพเจ้าพร้อมด้วย (ผู้ร่วมเดินทาง)');
            $table->string('destination')->nullable()->after('companions')->comment('สถานที่ปลายทาง (ณ)');
            $table->unsignedInteger('passengers')->nullable()->after('destination')->comment('จำนวนผู้โดยสาร');
            $table->string('fuel_source')->nullable()->after('passengers')->comment('แหล่งน้ำมัน: central/project/user');
            $table->unsignedInteger('attendees')->nullable()->after('fuel_source')->comment('จำนวนผู้เข้าประชุม (จองห้อง)');
            $table->string('file_path')->nullable()->after('attendees')->comment('ไฟล์เอกสารแนบ');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['division', 'companions', 'destination', 'passengers', 'fuel_source', 'attendees', 'file_path']);
        });
    }
};
