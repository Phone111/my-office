<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ผูกประกาศกับหน่วยงาน — เลขที่/ทะเบียนแยกตามโรงเรียน
     */
    public function up(): void
    {
        Schema::table('school_announcements', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('id')->constrained('units')->nullOnDelete();
            $table->index(['unit_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('school_announcements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
