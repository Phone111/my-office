<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ทะเบียนรับเงิน (AMSS การเงินฯ 2.2–2.4)
 *   money_class = budget        → รับเงินงบประมาณ (2.2)
 *               = nonbudget     → รับเงินนอกงบประมาณ (2.3) — เงินประกันสัญญา/อาหารกลางวัน/รายได้สถานศึกษา
 *               = state_revenue → รับเงินรายได้แผ่นดิน (2.4) — 6 ประเภท
 *   money_type_id → finance_masters(type=money_type) สำหรับนอกงบ/รายได้แผ่นดิน
 *   nature = ลักษณะรายการ (รับเงินสด/รับเช็ค/เงินฝากธนาคาร/อื่น ๆ)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->string('money_class', 20)->index();
            $table->foreignId('money_type_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->string('doc_no')->nullable();   // ที่เอกสาร
            $table->string('title');                 // รายการ
            $table->string('nature', 20)->nullable(); // ลักษณะรายการ
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('receive_date');
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_receipts');
    }
};
