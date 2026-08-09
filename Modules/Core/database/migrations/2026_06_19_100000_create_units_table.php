<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');                            // ชื่อหน่วยงาน/โรงเรียน
            $table->string('code')->nullable();                // รหัสหน่วยงาน/รหัสโรงเรียน
            $table->string('type')->default('school');         // area = สำนักงานเขต, school = โรงเรียน
            $table->foreignId('parent_id')->nullable()->constrained('units')->nullOnDelete(); // โรงเรียน → เขต
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // เริ่มต้น: สำนักงานเขต + โรงเรียนหลัก (เศรษฐบุตรบำเพ็ญ)
        $areaId = DB::table('units')->insertGetId([
            'name' => 'สำนักงานเขตพื้นที่การศึกษา',
            'type' => 'area',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('units')->insert([
            'name' => 'โรงเรียนเศรษฐบุตรบำเพ็ญ',
            'type' => 'school',
            'parent_id' => $areaId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
