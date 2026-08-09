<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->date('project_date')->nullable()->after('fiscal_year');  // วัน-เดือน-ปี
            $table->string('file_path')->nullable()->after('note');          // เอกสารแนบ
        });
    }

    public function down(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->dropColumn(['project_date', 'file_path']);
        });
    }
};
