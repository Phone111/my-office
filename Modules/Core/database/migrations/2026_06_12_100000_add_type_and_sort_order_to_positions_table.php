<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name')
                ->comment('ประเภท: executive/academic/staff');
            $table->unsignedInteger('sort_order')->default(0)->after('level')
                ->comment('ลำดับการแสดงผล');
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn(['type', 'sort_order']);
        });
    }
};
