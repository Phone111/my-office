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
        // วันลาสะสมของแต่ละคน ต่อประเภท ต่อปี
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->comment('ปี พ.ศ./ค.ศ.');
            $table->decimal('entitled_days', 5, 1)->default(0)->comment('สิทธิวันลาทั้งหมด');
            $table->decimal('used_days', 5, 1)->default(0)->comment('ใช้ไปแล้ว');
            $table->timestamps();

            $table->unique(['user_id', 'leave_type_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
