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
        // ห้องประชุม
        Schema::create('meeting_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อห้องประชุม');
            $table->string('location')->nullable()->comment('ที่ตั้ง/อาคาร');
            $table->unsignedInteger('capacity')->default(0)->comment('ความจุ (คน)');
            $table->boolean('is_active')->default(true)->comment('พร้อมใช้งาน');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_rooms');
    }
};
