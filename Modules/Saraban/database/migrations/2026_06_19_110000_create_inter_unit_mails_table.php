<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inter_unit_mails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_unit_id')->constrained('units')->cascadeOnDelete();   // หน่วยงานผู้ส่ง
            $table->foreignId('to_unit_id')->constrained('units')->cascadeOnDelete();      // หน่วยงานผู้รับ
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->nullable();          // เลขที่หนังสือ
            $table->date('doc_date');                      // ลงวันที่
            $table->string('subject');                     // เรื่อง
            $table->text('detail')->nullable();            // เนื้อหาโดยสรุป
            $table->string('priority')->default('normal'); // ปกติ/ด่วน/ด่วนมาก/ด่วนที่สุด
            $table->boolean('confidential')->default(false);
            $table->json('attachments')->nullable();
            $table->string('status')->default('sent');     // sent / received / forwarded
            $table->string('receive_number')->nullable();  // เลขทะเบียนรับ (ออกตอนลงรับ)
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // มอบให้บุคคล
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inter_unit_mails');
    }
};
