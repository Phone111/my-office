<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ประเภทความพิการ (นักเรียนพิการเรียนรวม) — รองรับนำเข้าจาก DMC
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('disability')->nullable()->after('status')->comment('ประเภทความพิการ (เรียนรวม)');
            $table->index('disability');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['disability']);
            $table->dropColumn('disability');
        });
    }
};
