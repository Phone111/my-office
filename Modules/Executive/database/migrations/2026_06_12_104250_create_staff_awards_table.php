<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สรุปรางวัล/ผลงานเชิดชูเกียรติของบุคลากร
     */
    public function up(): void
    {
        Schema::create('staff_awards', function (Blueprint $table) {
            $table->id();
            $table->string('staff_name')->comment('ชื่อบุคลากร');
            $table->string('award_name')->comment('ชื่อรางวัล');
            $table->string('level')->nullable()->comment('ระดับ เช่น โรงเรียน/เขต/จังหวัด/ชาติ');
            $table->string('awarded_by')->nullable()->comment('หน่วยงานที่มอบ');
            $table->date('awarded_date')->comment('วันที่ได้รับ');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_awards');
    }
};
