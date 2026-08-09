<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_trip_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('official_trip_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->foreignId('approver_id')->constrained('users');
            $table->string('status')->default('waiting')->comment('waiting/pending/approved/rejected');
            $table->text('comment')->nullable();
            $table->dateTime('acted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_trip_routes');
    }
};
