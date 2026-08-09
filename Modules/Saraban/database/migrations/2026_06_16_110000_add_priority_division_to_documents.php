<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ความเร่งด่วน (ปกติ/ด่วน/ด่วนมาก/ด่วนที่สุด) + ส่วนราชการ ของบันทึกข้อความ
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('priority')->default('normal')->after('is_urgent')->comment('normal/urgent/very_urgent/most_urgent');
            $table->string('division')->nullable()->after('priority')->comment('ส่วนราชการ/กลุ่มงาน');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['priority', 'division']);
        });
    }
};
