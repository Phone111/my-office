<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('group_id')->constrained('units')->nullOnDelete();
            }
        });

        // ผูกผู้ใช้เดิมทั้งหมดเข้ากับโรงเรียนหลัก
        $homeSchool = DB::table('units')->where('type', 'school')->orderBy('id')->value('id');
        if ($homeSchool) {
            DB::table('users')->whereNull('unit_id')->update(['unit_id' => $homeSchool]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
        });
    }
};
