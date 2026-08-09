<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เปลี่ยนแปลงสถานะเงิน (AMSS การเงินฯ ส่วนที่ 5)
 * เช่น ถอนเงินจากธนาคารเป็นเงินสด / นำเงินสดฝากธนาคาร / นำเงินส่งคลัง
 *   money_class = budget | nonbudget | state_revenue
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_status_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->string('money_class', 20)->index();
            $table->foreignId('money_type_id')->nullable()->constrained('finance_masters')->nullOnDelete();
            $table->string('doc_no')->nullable();
            $table->string('title');
            $table->string('nature', 20)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('change_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_status_changes');
    }
};
