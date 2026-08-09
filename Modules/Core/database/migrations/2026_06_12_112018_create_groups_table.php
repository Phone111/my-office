<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * กลุ่ม — กลุ่มบริหารงาน/กลุ่มงาน (แยกจาก "กลุ่มสาระ" = departments)
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อกลุ่ม');
            $table->string('code')->nullable()->unique()->comment('รหัสกลุ่ม');
            $table->text('description')->nullable();
            $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('หัวหน้ากลุ่ม');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
