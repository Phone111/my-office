<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('krs_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('krs_indicators')->cascadeOnDelete();
            $table->unsignedTinyInteger('round');          // รอบ 6 / 9 / 12 เดือน
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('submitted'); // submitted / received
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['indicator_id', 'round']);      // 1 รายงานต่อ ตัวชี้วัด+รอบ
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('krs_reports');
    }
};
