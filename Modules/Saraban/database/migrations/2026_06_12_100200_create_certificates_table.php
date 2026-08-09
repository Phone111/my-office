<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ทะเบียนเลขเกียรติบัตร — บันทึกการออกเลขเกียรติบัตรแต่ละใบ
     */
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->comment('เลขที่เกียรติบัตร (ออกอัตโนมัติ)');
            $table->string('title')->comment('ชื่อกิจกรรม/หลักสูตร');
            $table->string('recipient_name')->comment('ชื่อผู้รับเกียรติบัตร');
            $table->date('issued_date')->comment('วันที่ออก');
            $table->text('note')->nullable()->comment('หมายเหตุ');
            $table->foreignId('issuer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
