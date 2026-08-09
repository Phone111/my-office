<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม level (ลำดับ) และ type (สิทธิการใช้: executive/office) ให้ตาราง groups
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedInteger('level')->default(0)->after('name')->comment('ลำดับการแสดง');
            $table->string('type')->nullable()->after('level')
                ->comment('สิทธิการใช้: executive=กลุ่มผู้บริหาร, office=กลุ่มงานสำนักงาน');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['level', 'type']);
        });
    }
};
