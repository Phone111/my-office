<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มฟิลด์ "หนังสือเชิญประชุม + ตอบรับ" ให้หนังสือเวียน
 * is_meeting=true → ผู้รับกดตอบรับ (เข้าร่วม/ไม่เข้าร่วม/มอบผู้แทน) ได้
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circulars', function (Blueprint $table) {
            if (! Schema::hasColumn('circulars', 'is_meeting')) {
                $table->boolean('is_meeting')->default(false);
            }
            if (! Schema::hasColumn('circulars', 'meeting_at')) {
                $table->dateTime('meeting_at')->nullable();
            }
            if (! Schema::hasColumn('circulars', 'meeting_place')) {
                $table->string('meeting_place')->nullable();
            }
            if (! Schema::hasColumn('circulars', 'responses')) {
                $table->json('responses')->nullable(); // { userId: {status, note, at} }
            }
        });
    }

    public function down(): void
    {
        Schema::table('circulars', function (Blueprint $table) {
            $table->dropColumn(['is_meeting', 'meeting_at', 'meeting_place', 'responses']);
        });
    }
};
