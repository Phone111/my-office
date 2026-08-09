<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('executive_events', function (Blueprint $table) {
            if (! Schema::hasColumn('executive_events', 'audience')) {
                $table->json('audience')->nullable();          // ผู้ปฏิบัติ: ผอ./รองฯ/ผอ.กลุ่ม
            }
            if (! Schema::hasColumn('executive_events', 'time_text')) {
                $table->string('time_text')->nullable();        // เวลา เช่น "09.00 - 12.00 น."
            }
            if (! Schema::hasColumn('executive_events', 'days')) {
                $table->unsignedSmallInteger('days')->default(1); // จำนวนวัน
            }
        });
    }

    public function down(): void
    {
        Schema::table('executive_events', function (Blueprint $table) {
            $table->dropColumn(['audience', 'time_text', 'days']);
        });
    }
};
