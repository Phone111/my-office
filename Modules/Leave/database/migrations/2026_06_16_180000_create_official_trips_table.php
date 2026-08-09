<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->comment('เรื่อง');
            $table->text('companions')->nullable()->comment('พร้อมด้วย');
            $table->text('purpose')->comment('วัตถุประสงค์');
            $table->string('destination')->comment('สถานที่ไป (จังหวัด/หน่วยงาน)');
            $table->string('reference')->nullable()->comment('ตามคำสั่ง/หนังสือ');
            $table->dateTime('depart_at')->comment('ออกเดินทาง');
            $table->dateTime('return_at')->comment('กลับ');
            $table->string('vehicle_type')->default('official_car')->comment('พาหนะ');
            $table->string('vehicle_plate')->nullable()->comment('ทะเบียนรถ');
            $table->string('vehicle_other')->nullable()->comment('พาหนะอื่นๆ');
            $table->string('budget_source')->nullable()->comment('เบิกจากงบประมาณ');
            $table->string('document_number')->nullable()->comment('เลขที่หนังสือ');
            $table->json('attachments')->nullable();
            $table->string('status')->default('pending')->comment('draft/pending/approved/rejected');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_trips');
    }
};
