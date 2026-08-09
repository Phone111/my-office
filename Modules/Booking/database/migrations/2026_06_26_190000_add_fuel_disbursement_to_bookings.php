<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ใบเบิกน้ำมันเชื้อเพลิงและน้ำมันหล่อลื่น (AMSS ส่วน 11)
 * รายละเอียดการเบิกน้ำมันของการขอใช้รถที่อนุมัติแล้ว
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'fuel_station')) {
                $table->string('fuel_station')->nullable()->after('fuel_source');   // ปั๊ม/สถานที่เติม
                $table->decimal('fuel_liters', 8, 2)->nullable()->after('fuel_station'); // จำนวนลิตร
                $table->decimal('fuel_amount', 10, 2)->nullable()->after('fuel_liters'); // จำนวนเงิน (บาท)
                $table->string('fuel_note')->nullable()->after('fuel_amount');       // หมายเหตุ
                $table->timestamp('fuel_filled_at')->nullable()->after('fuel_note');  // บันทึกใบเบิกเมื่อ
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['fuel_station', 'fuel_liters', 'fuel_amount', 'fuel_note', 'fuel_filled_at']);
        });
    }
};
