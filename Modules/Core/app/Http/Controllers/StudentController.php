<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Student;
use Modules\Core\Models\Unit;

/**
 * ระบบข้อมูลนักเรียน — ทะเบียนนักเรียนรายคนต่อโรงเรียน + สรุปรายชั้น + นำเข้า CSV
 */
class StudentController extends Controller
{
    private function isOverseer(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'area_admin']);
    }

    private function ownerUnit(Request $request): int
    {
        $u = $request->user();
        if ($this->isOverseer($u) && $request->input('unit')) {
            return (int) $request->input('unit');
        }

        return (int) $u->unit_id;
    }

    private function guardScope(Request $request, Student $s): void
    {
        $allowed = $this->isOverseer($request->user()) || $s->unit_id === (int) $request->user()->unit_id;
        abort_unless($allowed, 403);
    }

    public function index(Request $request): Response
    {
        $unit = $this->ownerUnit($request);
        $overseer = $this->isOverseer($request->user());

        $q = Student::where('unit_id', $unit);
        if ($g = $request->input('grade')) {
            $q->where('grade', $g);
        }
        if ($room = $request->input('room')) {
            $q->where('room', $room);
        }
        if ($st = $request->input('status')) {
            $q->where('status', $st);
        }
        if ($kw = $request->input('q')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$kw%")->orWhere('surname', 'like', "%$kw%")->orWhere('student_code', 'like', "%$kw%"));
        }

        $rows = $q->orderBy('grade')->orderBy('room')->orderBy('name')->limit(1000)->get()
            ->map(fn (Student $s) => [
                'id' => $s->id,
                'student_code' => $s->student_code,
                'fullname' => trim($s->prename.$s->name.' '.$s->surname),
                'gender' => Student::GENDERS[$s->gender] ?? null,
                'grade' => $s->grade,
                'room' => $s->room,
                'status' => $s->status,
                'status_label' => Student::STATUSES[$s->status] ?? $s->status,
            ]);

        $base = Student::where('unit_id', $unit)->where('status', 'studying');

        return Inertia::render('Core::Students/Index', [
            'rows' => $rows,
            'filters' => $request->only(['grade', 'room', 'status', 'q']),
            'grades' => Student::GRADES,
            'genders' => collect(Student::GENDERS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'statuses' => collect(Student::STATUSES)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'unitName' => Unit::find($unit)?->name,
            'units' => $overseer ? Unit::schools()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]) : [],
            'selectedUnit' => $unit,
            'canPickSchool' => $overseer,
            'stats' => [
                'total' => (clone $base)->count(),
                'male' => (clone $base)->where('gender', 'M')->count(),
                'female' => (clone $base)->where('gender', 'F')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $unit = $this->ownerUnit($request);
        abort_unless($unit, 403, 'บัญชีของคุณยังไม่ได้สังกัดโรงเรียน');
        $v = $this->validateData($request, $unit);
        Student::create($v + ['unit_id' => $unit, 'created_by' => $request->user()->id]);

        return back()->with('success', 'เพิ่มข้อมูลนักเรียนเรียบร้อย');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->guardScope($request, $student);
        $student->update($this->validateData($request, (int) $student->unit_id, $student->id));

        return back()->with('success', 'อัปเดตข้อมูลนักเรียนเรียบร้อย');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->guardScope($request, $student);
        $student->delete();

        return back()->with('success', 'ลบข้อมูลนักเรียนแล้ว');
    }

    private function validateData(Request $request, int $unit, ?int $ignoreId = null): array
    {
        // เลขประจำตัว/เลขบัตร ห้ามซ้ำในโรงเรียนเดียวกัน (ข้ามค่าว่าง)
        $uniqueInUnit = fn (string $field) => Rule::unique('students', $field)
            ->where(fn ($q) => $q->where('unit_id', $unit))
            ->ignore($ignoreId);

        return $request->validate([
            'student_code' => ['nullable', 'string', 'max:50', $uniqueInUnit('student_code')],
            'citizen_id' => ['nullable', 'string', 'max:13', $uniqueInUnit('citizen_id')],
            'prename' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'surname' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'in:M,F'],
            'birthdate' => ['nullable', 'date'],
            'grade' => ['required', 'in:'.implode(',', Student::GRADES)],
            'room' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:'.implode(',', array_keys(Student::STATUSES))],
            'disability' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'student_code.unique' => 'เลขประจำตัวนักเรียนนี้มีอยู่แล้วในโรงเรียน',
            'citizen_id.unique' => 'เลขบัตรประชาชนนี้มีอยู่แล้วในโรงเรียน',
        ]);
    }

    public function report(Request $request): Response
    {
        $unit = $this->ownerUnit($request);
        $overseer = $this->isOverseer($request->user());

        $byGrade = collect(Student::GRADES)->map(function ($g) use ($unit) {
            $b = Student::where('unit_id', $unit)->where('status', 'studying')->where('grade', $g);

            return [
                'grade' => $g,
                'male' => (clone $b)->where('gender', 'M')->count(),
                'female' => (clone $b)->where('gender', 'F')->count(),
                'total' => (clone $b)->count(),
            ];
        })->filter(fn ($r) => $r['total'] > 0)->values();

        return Inertia::render('Core::Students/Report', [
            'byGrade' => $byGrade,
            'total' => Student::where('unit_id', $unit)->where('status', 'studying')->count(),
            'unitName' => Unit::find($unit)?->name,
            'units' => $overseer ? Unit::schools()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]) : [],
            'selectedUnit' => $unit,
            'canPickSchool' => $overseer,
        ]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, ['เลขประจำตัว', 'คำนำหน้า', 'ชื่อ', 'สกุล', 'เพศ(ชาย/หญิง)', 'ชั้น', 'ห้อง', 'เลขบัตรประชาชน']);
            fputcsv($out, ['10001', 'เด็กชาย', 'สมชาย', 'ใจดี', 'ชาย', 'ป.6', '1', '']);
            fclose($out);
        }, 'students_template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * นำเข้านักเรียน — รองรับทั้ง template ของเรา และไฟล์ DMC (อ่านจากชื่อหัวคอลัมน์)
     */
    public function import(Request $request): RedirectResponse
    {
        $unit = $this->ownerUnit($request);
        abort_unless($unit, 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:20480']]);

        $lines = array_map('str_getcsv', file($request->file('file')->getRealPath()));
        if (count($lines) < 2) {
            return back()->with('error', 'ไฟล์ว่างหรือไม่มีข้อมูล');
        }

        $header = array_map(fn ($h) => trim((string) $h), array_shift($lines));
        $col = $this->mapColumns($header);
        // จับหัว "ชื่อ" ไม่ได้ → ใช้ตำแหน่งตาม template ของเรา
        if (! isset($col['name'])) {
            $col = ['student_code' => 0, 'prename' => 1, 'name' => 2, 'surname' => 3, 'gender' => 4, 'grade' => 5, 'room' => 6, 'citizen_id' => 7];
        }
        $get = fn ($r, $key) => isset($col[$key]) ? trim((string) ($r[$col[$key]] ?? '')) : '';

        $count = 0;
        $skipped = 0;
        foreach ($lines as $r) {
            $name = $get($r, 'name');
            $grade = $this->normGrade($get($r, 'grade'));
            if ($name === '' || ! $grade) {
                $skipped++;

                continue;
            }
            $code = $get($r, 'student_code') ?: null;
            $cid = $get($r, 'citizen_id') ?: null;
            $values = [
                'unit_id' => $unit,
                'student_code' => $code,
                'citizen_id' => $cid,
                'prename' => $get($r, 'prename') ?: null,
                'name' => $name,
                'surname' => $get($r, 'surname'),
                'gender' => $this->normGender($get($r, 'gender')),
                'grade' => $grade,
                'room' => $get($r, 'room') ?: null,
                'disability' => $this->normDisability($get($r, 'disability')),
                'status' => 'studying',
                'created_by' => $request->user()->id,
            ];
            // มีเลขประจำตัว/เลขบัตร → อัปเดตคนเดิม · ไม่มีทั้งคู่ → สร้างใหม่เสมอ (กันชื่อซ้ำทับกัน)
            if ($code) {
                Student::updateOrCreate(['unit_id' => $unit, 'student_code' => $code], $values);
            } elseif ($cid) {
                Student::updateOrCreate(['unit_id' => $unit, 'citizen_id' => $cid], $values);
            } else {
                Student::create($values);
            }
            $count++;
        }

        $msg = "นำเข้านักเรียน {$count} คนเรียบร้อย".($skipped ? " (ข้าม {$skipped} แถวที่ข้อมูลไม่ครบ)" : '');

        return back()->with('success', $msg);
    }

    /**
     * รายงานนักเรียนพิการเรียนรวม — สรุปตามประเภท/โรงเรียน + รายชื่อ
     */
    public function disabilityReport(Request $request): Response
    {
        $user = $request->user();
        $overseer = $this->isOverseer($user);
        $unitParam = $request->input('unit');
        $type = $request->input('type');

        $scope = function () use ($overseer, $unitParam, $user) {
            $b = Student::query()->where('status', 'studying')
                ->whereNotNull('disability')->where('disability', '!=', '');
            if ($overseer) {
                if ($unitParam) {
                    $b->where('unit_id', (int) $unitParam);
                }
            } else {
                $b->where('unit_id', (int) $user->unit_id);
            }

            return $b;
        };

        $byType = (clone $scope())->select('disability', DB::raw('count(*) as c'))
            ->groupBy('disability')->orderByDesc('c')->get()
            ->map(fn ($r) => ['type' => $r->disability, 'count' => (int) $r->c]);

        $listQ = $scope();
        if ($type) {
            $listQ->where('disability', $type);
        }
        $students = $listQ->with('unit:id,name')->orderBy('unit_id')->orderBy('grade')->orderBy('room')->orderBy('name')->limit(2000)->get()
            ->map(fn (Student $s) => [
                'id' => $s->id,
                'school' => $s->unit?->name,
                'student_code' => $s->student_code,
                'fullname' => trim($s->prename.$s->name.' '.$s->surname),
                'grade' => $s->grade,
                'room' => $s->room,
                'disability' => $s->disability,
            ]);

        $bySchool = ($overseer && ! $unitParam)
            ? (clone $scope())->select('unit_id', DB::raw('count(*) as c'))->groupBy('unit_id')->orderByDesc('c')->get()
                ->map(fn ($r) => ['school' => Unit::find($r->unit_id)?->name, 'count' => (int) $r->c])->values()
            : collect();

        return Inertia::render('Core::Students/Disability', [
            'byType' => $byType,
            'total' => (clone $scope())->count(),
            'students' => $students,
            'bySchool' => $bySchool,
            'types' => array_values(Student::DISABILITY_TYPES),
            'activeType' => $type,
            'unitName' => $unitParam ? Unit::find($unitParam)?->name : ($overseer ? 'ทั้งเขต' : Unit::find($user->unit_id)?->name),
            'units' => $overseer ? Unit::schools()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]) : [],
            'selectedUnit' => $unitParam ? (int) $unitParam : null,
            'canPickSchool' => $overseer,
            'isAreaMode' => $overseer && ! $unitParam,
        ]);
    }

    /* ---------- ตัวช่วยนำเข้า (รองรับ DMC) ---------- */

    /** จับคู่ดัชนีคอลัมน์จากชื่อหัวตาราง */
    private function mapColumns(array $header): array
    {
        $col = [];
        foreach ($header as $i => $h) {
            $h = (string) $h;
            $lc = strtolower($h);
            if (! isset($col['citizen_id']) && (mb_strpos($h, 'ประชาชน') !== false || mb_strpos($h, 'บัตร') !== false || $lc === 'citizenid' || $lc === 'cid')) {
                $col['citizen_id'] = $i;
            } elseif (! isset($col['student_code']) && (mb_strpos($h, 'นักเรียน') !== false || (mb_strpos($h, 'ประจำตัว') !== false && mb_strpos($h, 'ประชาชน') === false) || $lc === 'studentid')) {
                $col['student_code'] = $i;
            } elseif (! isset($col['prename']) && (mb_strpos($h, 'นำหน้า') !== false || mb_strpos($h, 'คำนำ') !== false)) {
                $col['prename'] = $i;
            } elseif (! isset($col['surname']) && (mb_strpos($h, 'นามสกุล') !== false || $h === 'สกุล')) {
                $col['surname'] = $i;
            } elseif (! isset($col['name']) && mb_strpos($h, 'ชื่อ') !== false) {
                $col['name'] = $i;
            } elseif (! isset($col['gender']) && mb_strpos($h, 'เพศ') !== false) {
                $col['gender'] = $i;
            } elseif (! isset($col['room']) && mb_strpos($h, 'ห้อง') !== false) {
                $col['room'] = $i;
            } elseif (! isset($col['grade']) && (mb_strpos($h, 'ชั้น') !== false || mb_strpos($h, 'ระดับ') !== false)) {
                $col['grade'] = $i;
            } elseif (! isset($col['disability']) && mb_strpos($h, 'พิการ') !== false) {
                $col['disability'] = $i;
            }
        }

        return $col;
    }

    private function normGender(string $v): ?string
    {
        $v = trim($v);
        if (in_array($v, ['ชาย', 'ช', 'M', 'm', '1'], true)) {
            return 'M';
        }
        if (in_array($v, ['หญิง', 'ญ', 'F', 'f', '2'], true)) {
            return 'F';
        }

        return null;
    }

    private function normGrade(string $v): ?string
    {
        $g = str_replace(' ', '', trim($v));
        if ($g === '') {
            return null;
        }
        if (in_array($g, Student::GRADES, true)) {
            return $g;
        }
        if (preg_match('/(\d+)/u', $g, $m)) {
            $n = (int) $m[1];
            if (mb_strpos($g, 'อนุบาล') !== false || mb_substr($g, 0, 1) === 'อ') {
                $p = 'อ.';
            } elseif (mb_strpos($g, 'มัธยม') !== false || mb_substr($g, 0, 1) === 'ม') {
                $p = 'ม.';
            } elseif (mb_strpos($g, 'ประถม') !== false || mb_substr($g, 0, 1) === 'ป') {
                $p = 'ป.';
            } else {
                return null;
            }
            $cand = $p.$n;

            return in_array($cand, Student::GRADES, true) ? $cand : null;
        }

        return null;
    }

    /** แปลงข้อความความพิการ → ประเภทมาตรฐาน (เก็บตามเดิมถ้าไม่ตรง) */
    private function normDisability(string $v): ?string
    {
        $v = trim($v);
        if ($v === '' || in_array($v, ['ไม่พิการ', 'ปกติ', 'ไม่มี', '-', '0'], true)) {
            return null;
        }
        $map = [
            'เห็น' => 'บกพร่องทางการเห็น', 'ตาบอด' => 'บกพร่องทางการเห็น',
            'ได้ยิน' => 'บกพร่องทางการได้ยิน', 'หูหนวก' => 'บกพร่องทางการได้ยิน',
            'สติปัญญา' => 'บกพร่องทางสติปัญญา',
            'ร่างกาย' => 'บกพร่องทางร่างกาย/สุขภาพ', 'สุขภาพ' => 'บกพร่องทางร่างกาย/สุขภาพ', 'เคลื่อนไหว' => 'บกพร่องทางร่างกาย/สุขภาพ',
            'เรียนรู้' => 'บกพร่องทางการเรียนรู้ (LD)', 'LD' => 'บกพร่องทางการเรียนรู้ (LD)',
            'พูด' => 'บกพร่องทางการพูดและภาษา', 'ภาษา' => 'บกพร่องทางการพูดและภาษา',
            'พฤติกรรม' => 'บกพร่องทางพฤติกรรม/อารมณ์', 'อารมณ์' => 'บกพร่องทางพฤติกรรม/อารมณ์',
            'ออทิ' => 'ออทิสติก',
            'ซ้อน' => 'พิการซ้อน',
        ];
        foreach ($map as $kw => $label) {
            if (mb_stripos($v, $kw) !== false) {
                return $label;
            }
        }

        return mb_substr($v, 0, 100);
    }
}
