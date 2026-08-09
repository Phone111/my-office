<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * บันทึกการมาปฏิบัติราชการรายวันของบุคลากร (ลงเวลาโดยเจ้าหน้าที่)
     */
    public function up(): void
    {
        Schema::create('work_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('work_date')->comment('วันที่ปฏิบัติราชการ');
            $table->string('status')->default('present')
                ->comment('present/late/absent/trip/sick/personal/maternity/other_leave');
            $table->string('note')->nullable()->comment('หมายเหตุ');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_attendances');
    }
};
