<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_documents', function (Blueprint $table) {
            $table->timestamp('filed_at')->nullable()->after('read_at')->comment('เวลาที่ผู้รับจัดเก็บเข้าแฟ้ม');
        });
    }

    public function down(): void
    {
        Schema::table('personal_documents', function (Blueprint $table) {
            $table->dropColumn('filed_at');
        });
    }
};
