<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ลิงค์ไปราชการ (รถยนต์ราชการ) กับระบบจองรถ
     * vehicle_id = รถที่เลือกจากทะเบียน, vehicle_booking_id = การจองที่สร้างให้อัตโนมัติ
     */
    public function up(): void
    {
        Schema::table('official_trips', function (Blueprint $table) {
            if (! Schema::hasColumn('official_trips', 'vehicle_id')) {
                $table->unsignedBigInteger('vehicle_id')->nullable()->after('vehicle_type');
            }
            if (! Schema::hasColumn('official_trips', 'vehicle_booking_id')) {
                $table->unsignedBigInteger('vehicle_booking_id')->nullable()->after('vehicle_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('official_trips', function (Blueprint $table) {
            $table->dropColumn(['vehicle_id', 'vehicle_booking_id']);
        });
    }
};
