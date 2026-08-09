<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มเลขติดตาม (tracking_no) + เวลามอบหมาย (forwarded_at) ให้หนังสือระหว่างหน่วยงาน
 * ใช้ค้นหาติดตามสถานะหนังสือแบบไปรษณีย์ (ส่ง → รับ → มอบหมาย)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inter_unit_mails', function (Blueprint $table) {
            if (! Schema::hasColumn('inter_unit_mails', 'tracking_no')) {
                $table->string('tracking_no')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('inter_unit_mails', 'forwarded_at')) {
                $table->dateTime('forwarded_at')->nullable();
            }
        });

        // เติมเลขติดตามให้แถวเดิม (รูปแบบ RB + ปี ค.ศ. 2 หลัก + id 6 หลัก)
        DB::statement("UPDATE inter_unit_mails SET tracking_no = CONCAT('RB', DATE_FORMAT(created_at, '%y'), LPAD(id, 6, '0')) WHERE tracking_no IS NULL");
    }

    public function down(): void
    {
        Schema::table('inter_unit_mails', function (Blueprint $table) {
            $table->dropColumn(['tracking_no', 'forwarded_at']);
        });
    }
};
