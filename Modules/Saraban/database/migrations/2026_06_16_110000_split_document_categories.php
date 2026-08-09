<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * แยกหมวดหนังสือภายใน/เอกสารทั่วไป เป็น รับ/ส่ง
     * เอกสารเดิมถือเป็น "ส่ง" (ผู้ใช้เป็นผู้สร้าง)
     */
    public function up(): void
    {
        DB::table('documents')->where('category', 'internal')->update(['category' => 'internal_out']);
        DB::table('documents')->where('category', 'general')->update(['category' => 'general_out']);
    }

    public function down(): void
    {
        DB::table('documents')->where('category', 'internal_out')->update(['category' => 'internal']);
        DB::table('documents')->where('category', 'internal_in')->update(['category' => 'internal']);
        DB::table('documents')->where('category', 'general_out')->update(['category' => 'general']);
        DB::table('documents')->where('category', 'general_in')->update(['category' => 'general']);
    }
};
