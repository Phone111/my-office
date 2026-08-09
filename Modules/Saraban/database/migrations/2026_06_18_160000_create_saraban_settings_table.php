<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** ตั้งค่างานสารบรรณ (key-value) เช่น ปีสารบรรณปัจจุบัน */
    public function up(): void
    {
        Schema::create('saraban_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saraban_settings');
    }
};
