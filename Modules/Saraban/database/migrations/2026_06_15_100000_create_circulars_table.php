<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // หนังสือเวียนภายใน (broadcast ถึงกลุ่มงาน/กลุ่มสาระ)
        Schema::create('circulars', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('เรื่อง');
            $table->text('content')->nullable()->comment('รายละเอียด');
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sender_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->json('target_groups')->nullable()->comment('id กลุ่มงานที่ส่งถึง');
            $table->json('target_departments')->nullable()->comment('id กลุ่มสาระที่ส่งถึง');
            $table->json('attachments')->nullable()->comment('ไฟล์แนบ (paths)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circulars');
    }
};
