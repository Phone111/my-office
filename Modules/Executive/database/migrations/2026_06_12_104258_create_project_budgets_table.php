<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ผลการเบิกจ่ายงบประมาณรายโครงการ
     */
    public function up(): void
    {
        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('project_name')->comment('ชื่อโครงการ');
            $table->string('fiscal_year')->comment('ปีงบประมาณ เช่น 2569');
            $table->decimal('allocated_amount', 14, 2)->default(0)->comment('งบที่ได้รับจัดสรร');
            $table->decimal('disbursed_amount', 14, 2)->default(0)->comment('เบิกจ่ายแล้ว');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budgets');
    }
};
