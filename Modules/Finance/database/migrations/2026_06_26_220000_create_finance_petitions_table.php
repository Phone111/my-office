<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ทะเบียนฎีกา — ขอเบิกเงินคงคลัง (AMSS 3.3) และเงินกันเหลื่อมปี (AMSS 3.6)
 *   type = treasury  → ขอเบิกเงินคงคลัง (อ้างใบงวด, ตัดยอดใบงวด, รับเงินเข้าทะเบียนรับงบฯ)
 *        = carryover → เงินกันเหลื่อมปี
 * รวมหลายรายการขอเบิก/ขอยืม วางเบิกเป็นฎีกาเดียวภายใต้รหัสงบรายจ่ายเดียวกัน
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_petitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->string('type', 20)->default('treasury')->index();
            $table->string('petition_no')->nullable();   // เลขที่ฎีกา (GFMIS)
            $table->string('doc_no')->nullable();         // ที่เอกสาร
            $table->foreignId('allocation_id')->nullable()->constrained('finance_allocations')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->string('title');                      // รายการ
            $table->decimal('amount', 15, 2)->default(0); // จำนวนเงินขอเบิก
            $table->decimal('tax', 15, 2)->default(0);    // ภาษี
            $table->decimal('net', 15, 2)->default(0);    // รับจริง = amount - tax
            $table->boolean('cancelled')->default(false); // ยกเลิกฎีกา (3.5)
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_petitions');
    }
};
