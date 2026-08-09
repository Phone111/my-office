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
        // เส้นทางการเดินเอกสารจริง (สร้างจาก approval_flows ตอนส่งเอกสาร)
        Schema::create('document_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_order')->comment('ลำดับขั้น');
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('waiting')
                ->comment('waiting=รอคิว, pending=ถึงคิวรออนุมัติ, approved=อนุมัติ, rejected=ตีกลับ');
            $table->text('comment')->nullable()->comment('ความเห็นผู้อนุมัติ');
            $table->timestamp('acted_at')->nullable()->comment('เวลาที่ดำเนินการ');
            $table->timestamps();

            $table->unique(['document_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_routes');
    }
};
