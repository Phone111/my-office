<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete(); // หน่วยงานเจ้าของทะเบียน
            $table->unsignedInteger('cert_no');          // เลขที่เกียรติบัตร (วิ่งต่อหน่วยงาน/ปี)
            $table->smallInteger('cert_year');           // ปี พ.ศ.
            $table->string('category')->nullable();      // ประเภท/กิจกรรม
            $table->string('title');                     // ชื่อเรื่อง/รางวัล
            $table->string('recipient_name');            // ผู้รับเกียรติบัตร
            $table->string('recipient_org')->nullable(); // หน่วยงาน/โรงเรียนของผู้รับ
            $table->date('issued_date');
            $table->foreignId('signer_id')->nullable()->constrained('certificate_signers')->nullOnDelete();
            $table->string('note')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_certificates');
    }
};
