<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม group_id (กลุ่ม/ฝ่าย), type (สิทธิการใช้), sort_order (ลำดับ) ให้ departments
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('name')
                ->constrained('groups')->nullOnDelete()->comment('กลุ่ม/ฝ่ายที่สังกัด');
            $table->string('type')->nullable()->after('group_id')
                ->comment('สิทธิการใช้: executive=กลุ่มผู้บริหาร, department=กลุ่มสาระ');
            $table->unsignedInteger('sort_order')->default(0)->after('type')->comment('ลำดับการแสดง');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn(['type', 'sort_order']);
        });
    }
};
