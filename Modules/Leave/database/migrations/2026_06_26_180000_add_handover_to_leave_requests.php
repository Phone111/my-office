<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * รับมอบงาน (AMSS ส่วน 9) — ผู้ลามอบหมายงานให้ผู้ปฏิบัติหน้าที่แทน
 * ผู้ถูกมอบงานยืนยัน "รับมอบงาน" (handover_accepted_at)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_requests', 'handover_to')) {
                $table->foreignId('handover_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('leave_requests', 'handover_accepted_at')) {
                $table->timestamp('handover_accepted_at')->nullable()->after('handover_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handover_to');
            $table->dropColumn('handover_accepted_at');
        });
    }
};
