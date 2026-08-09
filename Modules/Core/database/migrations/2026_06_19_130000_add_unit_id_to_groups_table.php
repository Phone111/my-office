<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (! Schema::hasColumn('groups', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('id')->constrained('units')->nullOnDelete();
            }
        });

        // กลุ่มเดิมทั้งหมด = ของโรงเรียนหลัก
        $home = DB::table('units')->where('type', 'school')->orderBy('id')->value('id');
        if ($home) {
            DB::table('groups')->whereNull('unit_id')->update(['unit_id' => $home]);
        }
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
        });
    }
};
