<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // คนที่ไม่ได้ลงเวลา (เจ้าหน้าที่บันทึกแทน) จะไม่มีเวลาเข้างาน
            $table->time('check_in_time')->nullable()->change();
            $table->string('note')->nullable()->after('status')->comment('หมายเหตุการไม่ลงเวลา');
            $table->foreignId('recorded_by')->nullable()->after('note')
                ->constrained('users')->nullOnDelete()->comment('เจ้าหน้าที่ผู้บันทึก');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn('note');
            $table->time('check_in_time')->nullable(false)->change();
        });
    }
};
