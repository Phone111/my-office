<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // prefix เลขหนังสือของแต่ละหน่วยงาน เช่น "ศธ 04066"
        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'book_prefix')) {
                $table->string('book_prefix')->nullable()->after('code');
            }
        });

        Schema::table('inter_unit_mails', function (Blueprint $table) {
            if (! Schema::hasColumn('inter_unit_mails', 'send_seq')) {
                $table->unsignedInteger('send_seq')->nullable()->after('number'); // ลำดับเลขส่งต่อหน่วยงาน/ปี
            }
            if (! Schema::hasColumn('inter_unit_mails', 'reference')) {
                $table->string('reference')->nullable()->after('detail'); // อ้างถึงหนังสือ
            }
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('book_prefix');
        });
        Schema::table('inter_unit_mails', function (Blueprint $table) {
            $table->dropColumn(['send_seq', 'reference']);
        });
    }
};
