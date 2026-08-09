<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // รถยนต์ส่วนกลาง
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อ/รุ่นรถ');
            $table->string('license_plate')->comment('ทะเบียนรถ');
            $table->unsignedInteger('seats')->default(4)->comment('จำนวนที่นั่ง');
            $table->boolean('is_active')->default(true)->comment('พร้อมใช้งาน');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
