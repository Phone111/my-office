<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ทะเบียนคืนเงินโครงการ (AMSS 3.2) และคืนเงินคงคลัง (AMSS 3.4)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 3.2 คืนเงินโครงการ → คืนยอดเข้าโครงการ + เข้าทะเบียนรับเงินงบประมาณ
        Schema::create('finance_project_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->string('doc_no')->nullable();
            $table->string('title');
            $table->foreignId('project_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('return_date');
            $table->foreignId('receipt_id')->nullable()->constrained('finance_receipts')->nullOnDelete(); // รายการรับเงินงบฯ ที่สร้างคู่กัน
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3.4 คืนเงินคงคลัง → คืนยอดเข้าใบงวด + (option) สั่งจ่ายส่งคืนคลัง
        Schema::create('finance_treasury_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->string('doc_no')->nullable();
            $table->foreignId('allocation_id')->nullable()->constrained('finance_allocations')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->string('title');
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('to_payment')->default(true); // บันทึกในทะเบียนสั่งจ่ายฯ ด้วย
            $table->date('return_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_treasury_returns');
        Schema::dropIfExists('finance_project_returns');
    }
};
