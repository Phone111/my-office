<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เลขที่หนังสือต้นเรื่องของผู้ส่ง (สำหรับหนังสือ "รับ" จากภายนอก)
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'source_number')) {
                $table->string('source_number')->nullable()->after('source_date')->comment('เลขที่หนังสือต้นเรื่อง (ผู้ส่ง)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('source_number');
        });
    }
};
