<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // สร้าง Roles พื้นฐานจากโมดูล Core ก่อน
        $this->call([
            CoreDatabaseSeeder::class,
        ]);

        // ผู้ดูแลระบบเริ่มต้น
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'ผู้ดูแลระบบ',
                'password' => Hash::make('password'),
            ]
        );

        $admin->assignRole('admin');
    }
}
