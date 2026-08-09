<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ทะเบียนประวัติบุคลากร (ก.พ.7/ก.ค.ศ.16) + เครื่องราชอิสริยาภรณ์
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('citizen_id', 13)->nullable();   // เลขประจำตัวประชาชน
            $table->date('birthdate')->nullable();          // วันเดือนปีเกิด
            $table->char('gender', 1)->nullable();          // M/F
            $table->date('appointed_date')->nullable();     // วันบรรจุเข้ารับราชการ
            $table->string('education_level', 50)->nullable();  // วุฒิ (ป.ตรี/โท/เอก)
            $table->string('education_major', 150)->nullable(); // วิชาเอก
            $table->string('academic_standing', 80)->nullable();    // วิทยฐานะปัจจุบัน
            $table->date('academic_standing_date')->nullable();     // วันได้รับวิทยฐานะ
            $table->string('rank', 50)->nullable();         // ระดับ/อันดับ (คศ.1-5)
            $table->text('address')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('personnel_decorations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);                    // ชื่อเครื่องราชอิสริยาภรณ์
            $table->unsignedSmallInteger('year')->nullable(); // ปีที่ได้รับ (พ.ศ.)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_decorations');
        Schema::dropIfExists('personnel_profiles');
    }
};
