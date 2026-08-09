<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ผู้รักษาการในตำแหน่งผู้อำนวยการ — ใช้กับปุ่ม "เสนอรักษาการ" ในเส้นทางสารบรรณ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_acting_director')) {
                $table->boolean('is_acting_director')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_acting_director');
        });
    }
};
