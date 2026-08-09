<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('written_at')->nullable()->after('reason')->comment('เขียนที่');
            $table->date('written_date')->nullable()->after('written_at')->comment('วันที่เขียน');
            $table->string('contact_address')->nullable()->after('written_date')->comment('ที่อยู่ติดต่อระหว่างลา');
            $table->string('phone')->nullable()->after('contact_address')->comment('โทรศัพท์');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['written_at', 'written_date', 'contact_address', 'phone']);
        });
    }
};
