<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ข่าวสาร / ประกาศ
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('หัวข้อข่าว');
            $table->text('content')->comment('เนื้อหา');
            $table->string('file_path')->nullable()->comment('ไฟล์แนบ');
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
