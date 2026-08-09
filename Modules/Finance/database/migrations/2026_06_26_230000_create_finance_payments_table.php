<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ทะเบียนจ่าย (AMSS การเงินฯ ส่วนที่ 4)
 * วงจร: สั่งจ่าย (4.1–4.4) → อนุมัติจ่าย (4.5–4.6) → จ่ายเงิน (4.7–4.8)
 *   money_class = budget | nonbudget | state_revenue | advance(เงินทดรองราชการ)
 *   approval_status = pending(รอ) | approved(อนุมัติ) | rejected(ไม่อนุมัติ)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->string('money_class', 20)->index();
            $table->string('doc_no')->nullable();
            $table->foreignId('withdrawal_id')->nullable()->constrained('finance_withdrawals')->nullOnDelete();
            $table->foreignId('petition_id')->nullable()->constrained('finance_petitions')->nullOnDelete();
            $table->foreignId('money_type_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->string('title');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('payee')->nullable();
            $table->string('approval_status', 12)->default('pending')->index();
            $table->string('approve_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('paid')->default(false)->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_advance_return')->default(false); // เงินทดรอง: true = คืนเงินยืมทดรอง
            $table->date('order_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payments');
    }
};
