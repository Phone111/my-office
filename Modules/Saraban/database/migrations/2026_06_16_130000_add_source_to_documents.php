<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เก็บข้อมูลต้นทางสำหรับหนังสือ "รับ" (ผู้ส่ง + วันที่ส่งจริง)
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('source_name')->nullable()->after('creator_id')->comment('ผู้ส่ง (หนังสือรับ)');
            $table->dateTime('source_date')->nullable()->after('source_name')->comment('วันที่ส่งจริง (หนังสือรับ)');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['source_name', 'source_date']);
        });
    }
};
