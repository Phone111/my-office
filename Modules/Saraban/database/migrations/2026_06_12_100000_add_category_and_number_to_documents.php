<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มหมวดหมู่แฟ้ม + เลขทะเบียนเอกสารที่ออกอัตโนมัติ
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('category')->default('memo')->after('id')
                ->comment('memo=บันทึกข้อความ, incoming=รับหนังสือราชการ, outgoing=ส่งหนังสือราชการ, general=รับ/ส่งทั่วไป');
            $table->string('document_number')->nullable()->after('title')
                ->comment('เลขทะเบียนเอกสารที่ออกอัตโนมัติ');
            $table->timestamp('number_issued_at')->nullable()->after('document_number')
                ->comment('วันเวลาที่ออกเลขทะเบียน');

            $table->index(['category', 'creator_id']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['category', 'creator_id']);
            $table->dropColumn(['category', 'document_number', 'number_issued_at']);
        });
    }
};
