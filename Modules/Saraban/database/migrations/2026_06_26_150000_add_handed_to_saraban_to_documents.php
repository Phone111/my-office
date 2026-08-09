<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "นำส่งสารบรรณกลาง" — เจ้าของเรื่องนำส่งหนังสือที่อนุมัติแล้วให้สารบรรณกลางออกเลขส่ง
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'handed_to_saraban_id')) {
                $table->foreignId('handed_to_saraban_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handed_to_saraban_id');
        });
    }
};
