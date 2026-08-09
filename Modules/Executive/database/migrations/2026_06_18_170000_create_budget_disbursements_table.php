<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_budget_id')->constrained('project_budgets')->cascadeOnDelete();
            $table->date('disburse_date');               // วันที่เบิกจ่าย
            $table->decimal('amount', 12, 2);            // จำนวนเงิน
            $table->string('description')->nullable();   // รายการ/รายละเอียด
            $table->string('file_path')->nullable();     // เอกสารแนบ
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ย้ายยอดเบิกจ่ายเดิมของแต่ละโครงการมาเป็นรายการ "ยอดยกมา" (กันข้อมูลหาย)
        foreach (DB::table('project_budgets')->where('disbursed_amount', '>', 0)->get() as $p) {
            DB::table('budget_disbursements')->insert([
                'project_budget_id' => $p->id,
                'disburse_date' => $p->project_date ?? now()->toDateString(),
                'amount' => $p->disbursed_amount,
                'description' => 'ยอดยกมา',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_disbursements');
    }
};
