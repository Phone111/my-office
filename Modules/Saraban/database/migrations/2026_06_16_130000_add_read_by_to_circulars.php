<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * บันทึกรายชื่อผู้รับที่เปิดอ่านหนังสือเวียนแล้ว
     */
    public function up(): void
    {
        Schema::table('circulars', function (Blueprint $table) {
            $table->json('read_by')->nullable()->after('target_users')->comment('id ผู้รับที่อ่านแล้ว');
        });
    }

    public function down(): void
    {
        Schema::table('circulars', function (Blueprint $table) {
            $table->dropColumn('read_by');
        });
    }
};
