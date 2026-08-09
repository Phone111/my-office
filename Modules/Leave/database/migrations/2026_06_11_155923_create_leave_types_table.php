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
        // ประเภทการลา (ลาป่วย, ลากิจ, ไปราชการ ฯลฯ)
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อประเภทการลา');
            $table->string('code')->nullable()->unique()->comment('รหัส');
            $table->unsignedInteger('default_days')->default(0)->comment('สิทธิวันลาต่อปี (0 = ไม่จำกัด)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
