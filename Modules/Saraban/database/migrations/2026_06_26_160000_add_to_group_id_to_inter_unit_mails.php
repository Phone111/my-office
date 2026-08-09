<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เส้นทางหนังสือแบบ AMSS (ส่วน 16): สารบรรณกลาง → สารบรรณกลุ่ม(กลุ่มงาน) → บุคคลในกลุ่ม
 * to_group_id = กลุ่มงานที่สารบรรณกลางมอบให้ (เฉพาะหน่วยงานแบบเขต)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inter_unit_mails', function (Blueprint $table) {
            if (! Schema::hasColumn('inter_unit_mails', 'to_group_id')) {
                $table->foreignId('to_group_id')->nullable()->after('to_unit_id')->constrained('groups')->nullOnDelete();
            }
            if (! Schema::hasColumn('inter_unit_mails', 'assigned_group_at')) {
                $table->timestamp('assigned_group_at')->nullable()->after('forwarded_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inter_unit_mails', function (Blueprint $table) {
            $table->dropConstrainedForeignId('to_group_id');
            $table->dropColumn('assigned_group_at');
        });
    }
};
