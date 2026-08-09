<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มหมวดข่าว (category) ให้ตาราง news
     */
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('category')->nullable()->after('title')->comment('หมวดข่าว');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
