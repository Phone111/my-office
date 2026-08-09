<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete(); // โรงเรียน
            $table->string('student_code')->nullable();  // เลขประจำตัวนักเรียน
            $table->string('citizen_id', 13)->nullable();
            $table->string('prename')->nullable();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('gender', 1)->nullable();      // M/F
            $table->date('birthdate')->nullable();
            $table->string('grade');                       // ระดับชั้น
            $table->string('room')->nullable();            // ห้อง
            $table->string('status')->default('studying'); // studying/graduated/resigned/moved
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['unit_id', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
