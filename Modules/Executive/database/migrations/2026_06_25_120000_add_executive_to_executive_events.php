<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ผูกวาระกับผู้บริหารรายคน (แบบ affair AMSS) — เดิมเก็บเป็นบทบาท (audience)
     */
    public function up(): void
    {
        Schema::table('executive_events', function (Blueprint $table) {
            $table->foreignId('executive_id')->nullable()->after('id')
                ->constrained('users')->nullOnDelete()->comment('ผู้บริหารเจ้าของวาระ (รายคน)');
        });
    }

    public function down(): void
    {
        Schema::table('executive_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('executive_id');
        });
    }
};
