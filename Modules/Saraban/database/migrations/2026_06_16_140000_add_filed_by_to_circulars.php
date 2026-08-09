<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * รายชื่อผู้รับที่กด "จัดเก็บ" หนังสือเวียนแล้ว
     */
    public function up(): void
    {
        Schema::table('circulars', function (Blueprint $table) {
            $table->json('filed_by')->nullable()->after('read_by')->comment('id ผู้รับที่จัดเก็บแล้ว');
        });
    }

    public function down(): void
    {
        Schema::table('circulars', function (Blueprint $table) {
            $table->dropColumn('filed_by');
        });
    }
};
