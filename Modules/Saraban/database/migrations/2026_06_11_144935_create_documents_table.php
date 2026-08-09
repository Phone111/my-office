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
        // บันทึกข้อความ / เอกสาร
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('ชื่อเรื่อง');
            $table->text('content')->comment('เนื้อหา');
            $table->string('file_path')->nullable()->comment('ไฟล์แนบ');
            $table->string('status')->default('draft')
                ->comment('draft=ร่าง, pending=รออนุมัติ, approved=อนุมัติแล้ว, rejected=ตีกลับ');
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
