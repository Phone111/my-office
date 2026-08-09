<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ส่งเอกสารส่วนตัว — ส่งเอกสารถึงบุคคลโดยตรง (ไม่ผ่านเส้นทางอนุมัติ)
     */
    public function up(): void
    {
        Schema::create('personal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('read_at')->nullable()->comment('เวลาที่ผู้รับเปิดอ่าน');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_documents');
    }
};
