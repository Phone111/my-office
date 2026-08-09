<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เจ้าหน้าที่การเงินและสิทธิ์ (AMSS การเงินฯ 1.1)
 * อ้างอิงตามคำสั่งมอบหมายงานในหน้าที่ — กำหนดสิทธิ์รายบุคคล
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_approve')->default(false);     // ผู้อนุมัติสั่งจ่ายทุกประเภท
            $table->boolean('fund_allocation')->default(false);  // เงินงวด (คุมเงินประจำงวด)
            $table->boolean('can_withdraw')->default(false);     // ขอเบิก/ขอยืม
            $table->boolean('file_petition')->default(false);    // วางฎีกาเบิกเงินคงคลัง
            $table->boolean('budget_money')->default(false);     // เงินงบประมาณทุกขั้นตอน
            $table->boolean('nonbudget_money')->default(false);  // เงินนอกงบประมาณ
            $table->boolean('state_revenue')->default(false);    // เงินรายได้แผ่นดิน
            $table->boolean('advance_money')->default(false);    // เงินทดรองราชการ
            $table->boolean('pay_money')->default(false);        // จ่ายเงินทุกประเภท
            $table->boolean('view_reports')->default(true);      // เรียกดูรายงาน
            $table->timestamps();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_officers');
    }
};
