<?php

namespace Modules\Booking\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Booking\Models\MeetingRoom;
use Modules\Booking\Models\Vehicle;

class BookingDatabaseSeeder extends Seeder
{
    /**
     * ข้อมูลตัวอย่าง: รถยนต์และห้องประชุม
     */
    public function run(): void
    {
        $vehicles = [
            ['name' => 'Toyota Commuter', 'license_plate' => 'กข-1234', 'seats' => 12],
            ['name' => 'Isuzu D-Max', 'license_plate' => 'งจ-5678', 'seats' => 4],
        ];
        foreach ($vehicles as $v) {
            Vehicle::firstOrCreate(['license_plate' => $v['license_plate']], $v);
        }

        $rooms = [
            ['name' => 'ห้องประชุมใหญ่', 'location' => 'อาคาร 1 ชั้น 2', 'capacity' => 50],
            ['name' => 'ห้องประชุมเล็ก', 'location' => 'อาคาร 2 ชั้น 1', 'capacity' => 15],
        ];
        foreach ($rooms as $r) {
            MeetingRoom::firstOrCreate(['name' => $r['name']], $r);
        }
    }
}
