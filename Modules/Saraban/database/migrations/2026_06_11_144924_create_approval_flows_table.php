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
        // เส้นทางอนุมัติอัตโนมัติ กำหนดตามกลุ่มงาน (department)
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_order')->comment('ลำดับขั้นการอนุมัติ');
            $table->string('approver_role_name')->comment('ชื่อ role ของผู้อนุมัติขั้นนี้');
            $table->timestamps();

            // แต่ละกลุ่มงานมีได้ลำดับละหนึ่งขั้น
            $table->unique(['department_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_flows');
    }
};
