<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisions', function (Blueprint $table) {
            $table->foreignId('round_id')->nullable()->after('school_unit_id')->constrained('supervision_rounds')->nullOnDelete();
        });

        // คะแนนรายตัวชี้วัด (ปฏิบัติ/ไม่ปฏิบัติ + ระดับคุณภาพ 1-5)
        Schema::create('supervision_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervision_id')->constrained('supervisions')->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained('supervision_indicators')->cascadeOnDelete();
            $table->boolean('practiced')->nullable(); // ปฏิบัติ/ไม่ปฏิบัติ
            $table->tinyInteger('quality')->nullable(); // ระดับคุณภาพ 1-5
            $table->timestamps();
            $table->unique(['supervision_id', 'indicator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervision_scores');
        Schema::table('supervisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('round_id');
        });
    }
};
