<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตารางข้อมูลตั้งค่ารวม (master data) ของระบบการเงิน — AMSS การเงินฯ ส่วนตั้งค่าระบบ
 * ใช้ตารางเดียวแยกด้วย type:
 *   plan              = แผนงาน (1.3)
 *   project           = ผลผลิต/โครงการ (1.4)
 *   activity          = กิจกรรมหลัก (1.5)
 *   fund_source       = แหล่งของเงิน (1.6)
 *   expense_category  = งบรายจ่าย (1.7) — main_type = รหัสงบหลัก 1-7
 *   money_type        = ประเภท(ย่อย)ของเงิน (1.9) — main_type = ประเภทหลัก 1-3
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_masters', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();
            $table->unsignedSmallInteger('fiscal_year')->nullable()->index(); // null = ใช้ได้ทุกปี
            $table->string('code', 30)->nullable();
            $table->string('name', 255);
            $table->string('main_type', 30)->nullable(); // กลุ่มหลัก (งบรายจ่ายหลัก / ประเภทเงินหลัก)
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_masters');
    }
};
