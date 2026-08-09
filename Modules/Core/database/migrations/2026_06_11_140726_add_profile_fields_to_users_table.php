<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ความสัมพันธ์กับกลุ่มงานและตำแหน่ง
            $table->foreignId('department_id')
                ->nullable()
                ->after('email')
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('position_id')
                ->nullable()
                ->after('department_id')
                ->constrained('positions')
                ->nullOnDelete();

            // ข้อมูลโปรไฟล์เพิ่มเติม
            $table->string('profile_image')->nullable()->after('position_id')->comment('รูปโปรไฟล์');
            $table->string('phone', 20)->nullable()->after('profile_image')->comment('เบอร์โทรศัพท์');
            $table->string('id_plan')->nullable()->after('phone')->comment('เลขที่ตำแหน่งตามแผนอัตรากำลัง');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('position_id');
            $table->dropColumn(['profile_image', 'phone', 'id_plan']);
        });
    }
};
