<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_age_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete(); // เขตเจ้าของข้อมูล
            $table->string('citizen_id', 13)->nullable();
            $table->string('prename')->nullable();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('age_group')->nullable();         // 3-5 / 6-11 / 12-14
            $table->string('address')->nullable();           // ที่อยู่ย่อ
            $table->string('tambon')->nullable();
            $table->string('amphoe')->nullable();
            $table->string('province')->nullable();
            $table->foreignId('service_school_id')->nullable()->constrained('units')->nullOnDelete(); // ร.ร.ในเขตบริการ
            $table->boolean('enrolled')->default(false);     // เข้าเรียนแล้ว?
            $table->string('enroll_school')->nullable();     // เรียนที่โรงเรียน
            $table->string('non_enroll_reason')->nullable(); // สาเหตุไม่เข้าเรียน
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_age_children');
    }
};
