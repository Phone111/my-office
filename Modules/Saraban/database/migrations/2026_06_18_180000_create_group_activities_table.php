<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained('groups')->cascadeOnDelete();
            $table->date('activity_date');                 // วันที่
            $table->string('time_text')->nullable();       // เวลา เช่น "09:00 - 16:00 น."
            $table->unsignedSmallInteger('days')->default(1); // จำนวนวัน
            $table->string('title');                       // เรื่อง
            $table->text('detail')->nullable();            // รายละเอียด
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_activities');
    }
};
