<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ทะเบียนขอเบิก/ขอยืมโครงการ (AMSS 3.1)
 * บันทึกขอเบิก/ขอยืมเงินในแต่ละโครงการ → ก่อหนี้ผูกพัน (ตัดยอดโครงการ)
 *   kind = withdraw          → ขอเบิก
 *        = borrow_budget     → ขอยืมเงินงบประมาณ
 *        = borrow_nonbudget  → ขอยืมเงินนอกงบประมาณ
 *        = borrow_advance    → ขอยืมเงินทดรองราชการ
 * petition_id ถูกตั้งเมื่อรวมเข้าฎีกา (3.3); settled_at เมื่อส่งใช้เงินยืมแล้ว
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->string('doc_no')->nullable();          // ที่เอกสาร
            $table->string('kind', 20)->default('withdraw');
            $table->string('title');                        // รายการ
            $table->foreignId('project_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('borrower')->nullable();         // ชื่อผู้ขอเบิก/ขอยืม
            $table->foreignId('petition_id')->nullable()->constrained('finance_petitions')->nullOnDelete();
            $table->date('settled_at')->nullable();         // ส่งใช้เงินยืม
            $table->date('doc_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_withdrawals');
    }
};
