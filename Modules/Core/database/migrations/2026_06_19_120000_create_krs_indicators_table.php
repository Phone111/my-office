<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('krs_indicators', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');          // ปี พ.ศ.
            $table->string('category')->default('krs');    // krs / ars1..ars4
            $table->string('code');                        // ตัวชี้วัดที่ เช่น 1.1
            $table->string('name');                        // ชื่อตัวชี้วัด
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();  // ผู้รายงานตัวชี้วัด
            $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete();  // จนท.รับข้อมูล
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('krs_indicators');
    }
};
