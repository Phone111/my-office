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
        // การจองทรัพยากร (รถ/ห้องประชุม) แบบ polymorphic
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->morphs('bookable'); // bookable_type + bookable_id
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_at')->comment('เริ่มใช้งาน');
            $table->dateTime('end_at')->comment('สิ้นสุดการใช้งาน');
            $table->string('purpose')->comment('วัตถุประสงค์');
            $table->string('status')->default('booked')->comment('booked=จองแล้ว, cancelled=ยกเลิก');
            $table->timestamps();

            // ช่วยให้ค้นหาการชนกันของเวลาเร็วขึ้น
            $table->index(['bookable_type', 'bookable_id', 'start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
