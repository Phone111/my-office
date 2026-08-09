<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_signers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete(); // หน่วยงานเจ้าของ
            $table->string('name');                       // ชื่อผู้ลงนาม
            $table->string('position')->nullable();       // ตำแหน่ง (เช่น ผอ.สพป., ผอ.ร.ร.)
            $table->string('signature_path')->nullable(); // ภาพลายเซ็น
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_signers');
    }
};
