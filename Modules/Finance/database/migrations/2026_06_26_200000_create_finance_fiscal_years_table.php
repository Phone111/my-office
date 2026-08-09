<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ปีงบประมาณของระบบการเงิน (AMSS การเงินฯ 1.2)
 * แต่ละปีเก็บแยก ประมวลผลรายปี และกำหนด "ปีทำงานปัจจุบัน" ได้ 1 ปี
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique(); // พ.ศ. เช่น 2569
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fiscal_years');
    }
};
