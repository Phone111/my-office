<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * วันที่มีผล "ทั้งนี้ ตั้งแต่วันที่" สำหรับคำสั่ง (source_date ใช้เป็น "สั่ง ณ วันที่")
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'effective_date')) {
                $table->date('effective_date')->nullable()->after('source_number')->comment('ทั้งนี้ ตั้งแต่วันที่ (คำสั่ง)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('effective_date');
        });
    }
};
