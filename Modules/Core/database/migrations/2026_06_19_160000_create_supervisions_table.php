<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_unit_id')->nullable()->constrained('units')->nullOnDelete();   // เขตเจ้าของ
            $table->foreignId('school_unit_id')->constrained('units')->cascadeOnDelete();            // โรงเรียนที่รับการนิเทศ
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();    // ศึกษานิเทศก์/ผู้นิเทศ
            $table->date('visit_date');                 // วันที่นิเทศ/กำหนดนิเทศ
            $table->string('aspect');                   // ด้านการนิเทศ (academic/budget/personnel/general)
            $table->string('topic');                    // ประเด็น/เรื่องที่นิเทศ
            $table->text('objective')->nullable();      // วัตถุประสงค์
            $table->text('findings')->nullable();       // สภาพที่พบ/ผลการนิเทศ
            $table->text('recommendations')->nullable();// ข้อเสนอแนะ
            $table->string('rating')->nullable();       // ระดับผล (excellent/good/fair/improve)
            $table->json('attachments')->nullable();
            $table->string('status')->default('planned'); // planned | completed | acknowledged
            $table->text('school_response')->nullable(); // การตอบรับ/ดำเนินการของโรงเรียน
            $table->dateTime('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisions');
    }
};
