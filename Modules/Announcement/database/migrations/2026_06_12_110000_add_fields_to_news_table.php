<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (! Schema::hasColumn('news', 'excerpt')) {
                $table->string('excerpt')->nullable()->comment('รายละเอียดย่อสั้นๆ');
            }
            if (! Schema::hasColumn('news', 'author')) {
                $table->string('author')->nullable()->comment('ผู้บันทึก (ชื่อที่แสดง)');
            }
            if (! Schema::hasColumn('news', 'allow_comments')) {
                $table->boolean('allow_comments')->default(false)->comment('อนุญาตให้แสดงความคิดเห็น');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn(['excerpt', 'author', 'allow_comments']);
        });
    }
};
