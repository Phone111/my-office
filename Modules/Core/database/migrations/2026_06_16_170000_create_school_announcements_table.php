<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ประกาศโรงเรียน (ทางการ) — มีเลขที่รันต่อปี + แนบไฟล์
     */
    public function up(): void
    {
        Schema::create('school_announcements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->comment('ฉบับที่ (เลขรันต่อปี)');
            $table->unsignedSmallInteger('year')->comment('ปี พ.ศ. ที่ออกประกาศ');
            $table->string('title')->comment('เรื่อง');
            $table->date('announced_date')->comment('ประกาศ ณ วันที่');
            $table->json('attachments')->nullable()->comment('ไฟล์แนบ');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['year', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_announcements');
    }
};
