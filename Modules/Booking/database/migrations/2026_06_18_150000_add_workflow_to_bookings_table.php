<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->date('written_date')->nullable()->after('purpose');
            $table->string('driver_name')->nullable()->after('fuel_source');
            $table->foreignId('officer_id')->nullable()->after('driver_name')->constrained('users')->nullOnDelete();
            $table->text('officer_comment')->nullable()->after('officer_id');
            $table->foreignId('approver_id')->nullable()->after('officer_comment')->constrained('users')->nullOnDelete();
            $table->text('approver_comment')->nullable()->after('approver_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('officer_id');
            $table->dropConstrainedForeignId('approver_id');
            $table->dropColumn(['written_date', 'driver_name', 'officer_comment', 'approver_comment']);
        });
    }
};
