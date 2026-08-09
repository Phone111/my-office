<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_incoming_mails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete(); // หน่วยงานผู้รับ (เขต)
            $table->string('source_type');          // สพฐ. / ศธจ. / จังหวัด / อื่นๆ
            $table->string('source_name')->nullable(); // ชื่อหน่วยงานต้นทาง (ละเอียด)
            $table->string('number')->nullable();   // เลขที่หนังสือต้นทาง
            $table->date('doc_date');               // ลงวันที่ (ของต้นทาง)
            $table->string('subject');
            $table->text('detail')->nullable();
            $table->string('priority')->default('normal');
            $table->boolean('confidential')->default(false);
            $table->json('attachments')->nullable();
            $table->unsignedInteger('receive_no')->nullable();   // เลขทะเบียนรับ (วิ่งต่อหน่วยงาน/ปี)
            $table->smallInteger('receive_year')->nullable();    // ปี พ.ศ. ที่ออกเลขรับ
            $table->dateTime('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_group_id')->nullable()->constrained('groups')->nullOnDelete(); // มอบกลุ่มงาน
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();        // มอบบุคคล
            $table->string('note')->nullable();
            $table->string('status')->default('received'); // received | assigned
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_incoming_mails');
    }
};
