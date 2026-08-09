<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circulars', function (Blueprint $table) {
            $table->dropColumn(['target_groups', 'target_departments']);
            $table->json('target_users')->nullable()->after('sender_group_id')
                ->comment('id ผู้รับ (รายบุคคล)');
        });
    }

    public function down(): void
    {
        Schema::table('circulars', function (Blueprint $table) {
            $table->dropColumn('target_users');
            $table->json('target_groups')->nullable();
            $table->json('target_departments')->nullable();
        });
    }
};
