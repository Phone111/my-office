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
        // ใบลา / ขออนุญาตไปราชการ
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained();
            $table->date('start_date')->comment('วันเริ่มลา');
            $table->date('end_date')->comment('วันสิ้นสุด');
            $table->decimal('total_days', 5, 1)->comment('จำนวนวันลา');
            $table->text('reason')->comment('เหตุผล');
            $table->string('file_path')->nullable()->comment('เอกสารแนบ');
            $table->string('status')->default('draft')
                ->comment('draft/pending/approved/rejected');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
