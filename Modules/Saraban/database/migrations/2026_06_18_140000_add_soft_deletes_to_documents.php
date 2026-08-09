<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ทำลายหนังสือ = soft delete (เอกสารที่ทำลายหายจากทุกทะเบียน แต่เก็บไว้ในทะเบียนทำลาย)
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('documents', 'destroyed_by')) {
                $table->unsignedBigInteger('destroyed_by')->nullable()->after('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('destroyed_by');
        });
    }
};
