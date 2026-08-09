<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Models\Group;
use Modules\Core\Models\Unit;

/**
 * นำเข้าข้อมูลจาก AMSS (โรงเรียน / กลุ่มงาน / บุคลากรเขต / บุคลากรโรงเรียน)
 * - idempotent (รันซ้ำได้ ใช้ updateOrCreate)
 * - รหัสผ่านทุกคนตั้งใหม่เป็น 123456 (ไม่อ่าน/ถอดรหัสเดิมจาก AMSS)
 *
 * ตัวอย่าง: php artisan amss:import --all
 *           php artisan amss:import --host=43.241.58.1 --database=phoneddr_amss --username=phoneddr --password=secret --all
 */
class AmssImportCommand extends Command
{
    protected $signature = 'amss:import
        {--host=mysql} {--port=3306} {--database=legacy_amss} {--username=root} {--password=password}
        {--schools : นำเข้าเฉพาะโรงเรียน}
        {--workgroups : นำเข้าเฉพาะกลุ่มงานเขต}
        {--area-staff : นำเข้าเฉพาะบุคลากรเขต}
        {--school-staff : นำเข้าเฉพาะบุคลากรโรงเรียน}
        {--all : นำเข้าทั้งหมด}';

    protected $description = 'นำเข้าข้อมูลจริงจากระบบ AMSS (โรงเรียน/กลุ่มงาน/บุคลากร)';

    /** ตำแหน่งเขต (person_position) → [ระดับ, ...หน้าที่ย่อย] */
    private const AREA_ROLE = [
        1 => ['executive', 'director'],       // ผอ.เขต/ผอ.กลุ่ม
        2 => ['executive', 'deputy_director'], // รองผอ.
        3 => ['head'],                        // หัวหน้ากลุ่ม/งาน
        4 => ['officer', 'supervisor'],       // ศึกษานิเทศก์ = เจ้าหน้าที่ + หน้าที่นิเทศ
    ];

    /** ตำแหน่งโรงเรียน (person_sch_position) → [ระดับ, ...หน้าที่ย่อย] */
    private const SCHOOL_ROLE = [
        1 => ['officer', 'school_clerk'],     // สารบรรณโรงเรียน = เจ้าหน้าที่ + หน้าที่สารบรรณ
        2 => ['executive', 'director'],       // ผอ.โรงเรียน
        3 => ['executive', 'deputy_director'], // รองผอ.โรงเรียน
    ];

    public function handle(): int
    {
        config(['database.connections.amss_src' => [
            'driver' => 'mysql',
            'host' => $this->option('host'),
            'port' => $this->option('port'),
            'database' => $this->option('database'),
            'username' => $this->option('username'),
            'password' => $this->option('password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);

        try {
            $src = DB::connection('amss_src');
            $src->getPdo();
        } catch (\Throwable $e) {
            $this->error('เชื่อมต่อฐานข้อมูล AMSS ไม่ได้: '.$e->getMessage());

            return self::FAILURE;
        }

        $area = Unit::area()->first();
        if (! $area) {
            $this->error('ไม่พบหน่วยงานเขต (area) ในระบบ — โปรด seed AreaUnit ก่อน');

            return self::FAILURE;
        }

        // ถ้าไม่ระบุ flag ใด ๆ = ทำทั้งหมด
        $only = array_filter([
            'schools' => $this->option('schools'),
            'workgroups' => $this->option('workgroups'),
            'area-staff' => $this->option('area-staff'),
            'school-staff' => $this->option('school-staff'),
        ]);
        $all = $this->option('all') || empty($only);

        // username จาก system_user (key = person_id)
        $usernames = $src->table('system_user')->pluck('username', 'person_id');

        if ($all || isset($only['schools'])) {
            $this->importSchools($src, $area);
        }
        if ($all || isset($only['workgroups'])) {
            $this->importWorkgroups($src, $area);
        }
        if ($all || isset($only['area-staff'])) {
            $this->importAreaStaff($src, $area, $usernames);
        }
        if ($all || isset($only['school-staff'])) {
            $this->importSchoolStaff($src, $area, $usernames);
        }

        $this->info('นำเข้าข้อมูล AMSS เสร็จสมบูรณ์');

        return self::SUCCESS;
    }

    private function importSchools($src, Unit $area): void
    {
        $rows = $src->table('system_school')->get();
        $this->info("นำเข้าโรงเรียน {$rows->count()} แห่ง...");
        $bar = $this->output->createProgressBar($rows->count());
        foreach ($rows as $r) {
            Unit::updateOrCreate(
                ['code' => (string) $r->school_code],
                ['name' => $r->school_name, 'type' => Unit::TYPE_SCHOOL, 'parent_id' => $area->id, 'is_active' => true],
            );
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    private function importWorkgroups($src, Unit $area): void
    {
        $rows = $src->table('system_workgroup')->get();
        $this->info("นำเข้ากลุ่มงานเขต {$rows->count()} กลุ่ม...");
        foreach ($rows as $r) {
            Group::updateOrCreate(
                ['unit_id' => $area->id, 'name' => $r->workgroup_desc],
                ['level' => (int) ($r->workgroup_order ?? 0), 'is_active' => true],
            );
        }
    }

    private function importAreaStaff($src, Unit $area, $usernames): void
    {
        $total = $src->table('person_main')->count();
        $this->info("นำเข้าบุคลากรเขต {$total} คน...");
        $bar = $this->output->createProgressBar($total);
        $src->table('person_main')->orderBy('id')->chunk(500, function ($people) use ($area, $usernames, $bar) {
            foreach ($people as $p) {
                $this->upsertUser($p, $area->id, self::AREA_ROLE[$p->position_code] ?? ['staff'], $usernames);
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine();
    }

    private function importSchoolStaff($src, Unit $area, $usernames): void
    {
        // map school_code (8 หลัก) → unit id ที่เพิ่ง import
        $unitByCode = Unit::where('parent_id', $area->id)->pluck('id', 'code');

        $total = $src->table('person_sch_main')->where('school_code', '!=', 0)->count();
        $this->info("นำเข้าบุคลากรโรงเรียน {$total} คน...");
        $bar = $this->output->createProgressBar($total);
        $missing = 0;
        $src->table('person_sch_main')->where('school_code', '!=', 0)->orderBy('id')
            ->chunk(500, function ($people) use ($unitByCode, $usernames, $bar, &$missing) {
                foreach ($people as $p) {
                    $unitId = $unitByCode[(string) $p->school_code] ?? null;
                    if (! $unitId) {
                        $missing++;
                        $bar->advance();

                        continue;
                    }
                    $this->upsertUser($p, $unitId, self::SCHOOL_ROLE[$p->position_code] ?? ['staff'], $usernames);
                    $bar->advance();
                }
            });
        $bar->finish();
        $this->newLine();
        if ($missing) {
            $this->warn("ข้าม {$missing} คน (ไม่พบโรงเรียนที่ตรงกับ school_code — อาจยังไม่ได้ import โรงเรียน)");
        }
    }

    /** สร้าง/อัปเดตผู้ใช้ 1 คน + กำหนด role [ระดับ, ...หน้าที่ย่อย] (idempotent ด้วย email = person_id@amss.local) */
    private function upsertUser($p, int $unitId, array $roles, $usernames): void
    {
        $pid = $p->person_id;
        $username = $usernames[$pid] ?? $pid;
        $fullname = trim($p->prename.$p->name.' '.$p->surname);

        $attrs = ['name' => $fullname, 'username' => $username, 'unit_id' => $unitId, 'password' => Hash::make('123456')];
        try {
            $user = User::updateOrCreate(['email' => $pid.'@amss.local'], $attrs);
        } catch (\Throwable $e) {
            // username ชนกับคนอื่น → ใช้ person_id เป็น username แทน
            $attrs['username'] = $pid;
            $user = User::updateOrCreate(['email' => $pid.'@amss.local'], $attrs);
        }
        $user->syncRoles($roles);
    }
}
