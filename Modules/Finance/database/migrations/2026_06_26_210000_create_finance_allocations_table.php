<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ทะเบียนการจัดสรรงบประมาณ / เงินงวด (AMSS การเงินฯ 2.1)
 * บันทึกการรับอนุมัติเงินประจำงวดตามหนังสือโอนเปลี่ยนแปลงการจัดสรรงบประมาณจาก สพฐ.
 * แต่ละแถว = 1 ใบงวด (เลขที่ใบงวดรันอัตโนมัติต่อปีงบประมาณ) — เป็นยอดตั้งต้นให้ทุกการเบิกตัดยอด
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->unsignedInteger('voucher_no');                 // เลขที่ใบงวด (รันต่อปี)
            $table->string('doc_no')->nullable();                  // หนังสือเลขที่
            $table->date('doc_date')->nullable();                  // ลงวันที่
            $table->string('allocation_ref')->nullable();          // อ้างถึงหนังสือจัดสรร
            $table->foreignId('plan_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->string('activity_extra')->nullable();          // กิจกรรมหลักเพิ่มเติม
            $table->foreignId('fund_source_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->string('account_code')->nullable();            // รหัสทางบัญชี
            $table->foreignId('expense_category_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->string('title');                               // รายการ
            $table->text('detail')->nullable();                    // รายละเอียด
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('received_at');
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['fiscal_year', 'voucher_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_allocations');
    }
};
