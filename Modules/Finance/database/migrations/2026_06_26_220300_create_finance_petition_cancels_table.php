<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ทะเบียนยกเลิกฎีกา (AMSS 3.5)
 * บันทึกการยกเลิกฎีกาที่วางเบิกเงินจากระบบ GFMIS
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_petition_cancels', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->foreignId('petition_id')->nullable()->constrained('finance_petitions')->nullOnDelete();
            $table->string('petition_no')->nullable();  // เลขที่ฎีกา (กรณีไม่ผูกกับฎีกาในระบบ)
            $table->string('ref_doc')->nullable();       // ที่เอกสารอ้างอิง
            $table->string('reason');                    // สาเหตุการยกเลิก
            $table->date('cancel_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_petition_cancels');
    }
};
