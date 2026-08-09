<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Group;
use Modules\Core\Models\Position;
use Modules\Core\Models\Unit;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * นำเข้าข้อมูลจากระบบเก่า (My-Office / AMSS++) ผ่านไฟล์ CSV
 */
class ImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Core::Import', [
            'roles' => Role::orderBy('id')->pluck('name'),
            'unitCount' => Unit::schools()->count(),
            'userCount' => User::count(),
        ]);
    }

    /** อ่าน CSV → array ของแถว (assoc ตาม header) */
    private function readCsv(Request $request): array
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $lines = array_filter(array_map('rtrim', file($request->file('file')->getRealPath())));
        if (empty($lines)) {
            return [];
        }
        $header = array_map(fn ($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', $h))), str_getcsv(array_shift($lines), ',', '"', ''));
        $rows = [];
        foreach ($lines as $line) {
            $cells = str_getcsv($line, ',', '"', '');
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = isset($cells[$i]) ? trim($cells[$i]) : null;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** นำเข้าโรงเรียน/หน่วยงาน */
    public function importUnits(Request $request): RedirectResponse
    {
        $rows = $this->readCsv($request);
        $area = Unit::area()->first();
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $n => $r) {
            $name = $r['name'] ?? null;
            if (! $name) {
                $errors[] = 'แถว '.($n + 2).': ไม่มีชื่อ';

                continue;
            }
            $unit = Unit::firstOrNew(['name' => $name, 'type' => Unit::TYPE_SCHOOL]);
            $exists = $unit->exists;
            $unit->fill([
                'code' => $r['code'] ?? $unit->code,
                'address' => $r['address'] ?? $unit->address,
                'phone' => $r['phone'] ?? $unit->phone,
                'parent_id' => $area?->id,
                'is_active' => true,
            ])->save();
            $exists ? $updated++ : $created++;
        }

        return back()->with('success', "นำเข้าโรงเรียน: เพิ่ม {$created} · อัปเดต {$updated}".(count($errors) ? ' · ข้าม '.count($errors) : ''))
            ->with('importErrors', $errors);
    }

    /** นำเข้าบุคลากร */
    public function importUsers(Request $request): RedirectResponse
    {
        $rows = $this->readCsv($request);
        $created = 0;
        $updated = 0;
        $errors = [];

        $roleByLabel = [
            'ผู้ดูแลระบบ' => 'admin', 'ผู้อำนวยการ' => 'director', 'รองผู้อำนวยการ' => 'deputy_director',
            'หัวหน้ากลุ่ม' => 'head_of_department', 'หัวหน้ากลุ่มสาระ' => 'head_of_subject',
            'ครู' => 'teacher', 'ธุรการกลุ่ม' => 'group_clerk', 'สารบรรณกลาง' => 'saraban',
            'เลขานุการ' => 'secretary', 'เจ้าหน้าที่วันลา' => 'leave_officer',
        ];

        foreach ($rows as $n => $r) {
            $name = $r['name'] ?? null;
            $username = $r['username'] ?? ($r['email'] ?? null);
            if (! $name || ! $username) {
                $errors[] = 'แถว '.($n + 2).': ต้องมี name และ username/email';

                continue;
            }

            // แยกความผิดพลาดรายแถว — แถวเดียวพังไม่ทำให้ทั้งไฟล์ล้ม
            try {
                // resolve relations
                $unitId = ! empty($r['unit']) ? Unit::where('name', $r['unit'])->value('id') : null;
                $groupId = ! empty($r['group']) ? Group::where('name', $r['group'])->value('id') : null;
                $positionId = ! empty(trim($r['position'] ?? '')) ? Position::firstOrCreate(['name' => trim($r['position'])], ['is_active' => true])->id : null;

                $user = User::firstOrNew(['username' => $username]);
                $isNew = ! $user->exists;
                $user->fill([
                    'name' => $name,
                    'email' => $r['email'] ?? ($username.'@import.local'),
                    'phone' => $r['phone'] ?? $user->phone,
                    'unit_id' => $unitId ?? $user->unit_id,
                    'group_id' => $groupId ?? $user->group_id,
                    'position_id' => $positionId ?? $user->position_id,
                ]);
                if ($isNew) {
                    $user->password = Hash::make('123456');
                }
                $user->save();

                // role
                if (! empty($r['role'])) {
                    $code = Role::where('name', $r['role'])->exists() ? $r['role'] : ($roleByLabel[$r['role']] ?? null);
                    if ($code) {
                        $user->syncRoles([$code]);
                    } else {
                        $errors[] = 'แถว '.($n + 2).": role ไม่รู้จัก ({$r['role']})";
                    }
                }

                $isNew ? $created++ : $updated++;
            } catch (\Throwable $e) {
                $errors[] = 'แถว '.($n + 2).': '.$e->getMessage();
            }
        }

        return back()->with('success', "นำเข้าบุคลากร: เพิ่ม {$created} · อัปเดต {$updated}".(count($errors) ? ' · มีปัญหา '.count($errors) : '').' · รหัสผ่านใหม่ = 123456')
            ->with('importErrors', $errors);
    }

    /** ดาวน์โหลดเทมเพลต CSV */
    public function template(string $type): StreamedResponse
    {
        $data = $type === 'users'
            ? ["name,username,email,phone,role,unit,group,position", "นายทดสอบ ใจดี,testuser,test@x.com,0810000000,teacher,โรงเรียนเศรษฐบุตรบำเพ็ญ,,ครู"]
            : ["name,code,address,phone", "โรงเรียนตัวอย่าง,1001,123 ถนน...,021234567"];

        $filename = "template_{$type}.csv";

        return response()->stream(function () use ($data) {
            echo "\xEF\xBB\xBF"; // BOM ให้ Excel อ่านไทยถูก
            echo implode("\r\n", $data);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
