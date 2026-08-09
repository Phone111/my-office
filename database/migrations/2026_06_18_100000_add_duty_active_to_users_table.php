<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // แสดงในบอร์ด "ผู้บริหารปฏิบัติราชการ" บนหน้าหลักหรือไม่
            $table->boolean('duty_active')->default(false)->after('id_plan');
            // ลำดับการแสดง (น้อย = อยู่บน)
            $table->unsignedInteger('duty_order')->default(0)->after('duty_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['duty_active', 'duty_order']);
        });
    }
};
