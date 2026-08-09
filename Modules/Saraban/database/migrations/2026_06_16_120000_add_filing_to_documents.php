<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * การจัดเก็บเอกสาร (แฟ้ม) — บันทึกวิธีจัดเก็บ/นำส่งหลังอนุมัติ
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('filing')->nullable()->after('division')->comment('สถานะการจัดเก็บ/นำส่ง');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('filing');
        });
    }
};
