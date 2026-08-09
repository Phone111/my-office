<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ตัวนับเลขทะเบียนกลาง — แยกตาม "เล่มทะเบียน" (book) และปี (พ.ศ.)
     * ใช้ออกเลขรันทั้งเอกสารและเกียรติบัตร
     */
    public function up(): void
    {
        Schema::create('document_counters', function (Blueprint $table) {
            $table->id();
            $table->string('book')->comment('เล่มทะเบียน เช่น incoming, outgoing, certificate');
            $table->unsignedInteger('year')->comment('ปี พ.ศ.');
            $table->unsignedInteger('last_no')->default(0)->comment('เลขล่าสุดที่ออกไปแล้ว');
            $table->timestamps();

            $table->unique(['book', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_counters');
    }
};
