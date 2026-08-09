<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\AchievementResult;
use Modules\Core\Models\Unit;

/**
 * ผลสัมฤทธิ์ระดับชาติ (O-NET / NT / RT)
 * เขตกรอก/นำเข้าได้ทุกโรงเรียน · โรงเรียนกรอกเฉพาะของตน · รายงานเทียบค่าเฉลี่ยเขต
 */
class AchievementController extends Controller
{
    private function isOverseer(Request $request): bool
    {
        return $request->user()->hasAnyRole(['admin', 'area_admin', 'secretary', 'supervisor']);
    }

    public function index(Request $request): Response
    {
        $type = $request->query('type', 'onet');
        if (! isset(AchievementResult::TEST_TYPES[$type])) {
            $type = 'onet';
        }
        $grades = AchievementResult::GRADES[$type];
        $grade = $request->query('grade', $grades[0]);
        if (! in_array($grade, $grades, true)) {
            $grade = $grades[0];
        }
        $currentYear = now()->year + 543;
        $year = (int) $request->query('year', $currentYear);

        $subjects = AchievementResult::SUBJECTS[$type];
        $overseer = $this->isOverseer($request);

        $schools = Unit::schools()->where('is_active', true)
            ->when(! $overseer, fn ($q) => $q->where('id', $request->user()->unit_id))
            ->orderBy('name')->get(['id', 'name']);

        // คะแนนที่มี: unit_id => { subject => score }
        $existing = AchievementResult::where('test_type', $type)
            ->where('academic_year', $year)->where('grade', $grade)
            ->whereIn('unit_id', $schools->pluck('id'))
            ->get()->groupBy('unit_id')
            ->map(fn ($rows) => $rows->pluck('score', 'subject'));

        $rows = $schools->map(function (Unit $s) use ($existing, $subjects) {
            $scores = $existing[$s->id] ?? collect();

            return [
                'unit_id' => $s->id,
                'name' => $s->name,
                'scores' => collect($subjects)->keys()->mapWithKeys(fn ($k) => [
                    $k => isset($scores[$k]) ? (float) $scores[$k] : null,
                ]),
            ];
        });

        // ค่าเฉลี่ยเขต ต่อวิชา (เฉพาะที่มีคะแนน)
        $areaAvg = collect($subjects)->keys()->mapWithKeys(function ($k) use ($rows) {
            $vals = $rows->pluck('scores.'.$k)->filter(fn ($v) => $v !== null);

            return [$k => $vals->count() ? round($vals->avg(), 2) : null];
        });

        // กราฟเปรียบเทียบรายปี — ค่าเฉลี่ยเขตต่อวิชา ย้อนหลัง 5 ปี (เฉพาะปีที่มีข้อมูล)
        $trend = collect(range($currentYear, $currentYear - 4))
            ->map(function ($y) use ($type, $grade, $subjects, $schools) {
                $avgs = AchievementResult::where('test_type', $type)->where('academic_year', $y)
                    ->where('grade', $grade)->whereIn('unit_id', $schools->pluck('id'))
                    ->whereNotNull('score')->selectRaw('subject, avg(score) as a')
                    ->groupBy('subject')->pluck('a', 'subject');

                return [
                    'year' => $y,
                    'scores' => collect($subjects)->keys()->mapWithKeys(fn ($k) => [
                        $k => isset($avgs[$k]) ? round((float) $avgs[$k], 2) : null,
                    ]),
                ];
            })
            ->filter(fn ($t) => collect($t['scores'])->filter(fn ($v) => $v !== null)->isNotEmpty())
            ->sortBy('year')->values();

        return Inertia::render('Core::Achievement/Index', [
            'testTypes' => AchievementResult::TEST_TYPES,
            'subjects' => $subjects,
            'gradesForType' => $grades,
            'filters' => ['type' => $type, 'grade' => $grade, 'year' => $year],
            'years' => range($currentYear, $currentYear - 6),
            'rows' => $rows,
            'areaAvg' => $areaAvg,
            'trend' => $trend,
            'canEdit' => $schools->isNotEmpty(),
            'isOverseer' => $overseer,
        ]);
    }

    /** ดาวน์โหลดเทมเพลต CSV (รหัสโรงเรียน + คอลัมน์วิชา) */
    public function template(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $type = isset(AchievementResult::TEST_TYPES[$request->query('type')]) ? $request->query('type') : 'onet';
        $subjects = AchievementResult::SUBJECTS[$type];
        $header = array_merge(['รหัสโรงเรียน'], array_values($subjects));

        return response()->streamDownload(function () use ($header) {
            $h = fopen('php://output', 'w');
            fwrite($h, "\xEF\xBB\xBF"); // BOM ให้ Excel อ่านไทยถูก
            fputcsv($h, $header);
            fclose($h);
        }, "achievement-template-{$type}.csv", ['Content-Type' => 'text/csv']);
    }

    /** นำเข้าคะแนนจากไฟล์ CSV (รหัสโรงเรียน, คะแนนรายวิชา) */
    public function import(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'type' => ['required', Rule::in(array_keys(AchievementResult::TEST_TYPES))],
            'year' => ['required', 'integer', 'min:2400', 'max:2700'],
            'grade' => ['required', 'string', 'max:10'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $subjects = array_keys(AchievementResult::SUBJECTS[$v['type']]);
        $overseer = $this->isOverseer($request);
        $myUnit = $request->user()->unit_id;

        // map รหัสโรงเรียน → unit_id (เฉพาะที่ผู้ใช้มีสิทธิ์)
        $unitByCode = Unit::schools()->where('is_active', true)
            ->when(! $overseer, fn ($q) => $q->where('id', $myUnit))
            ->pluck('id', 'code');

        // แผนที่คอลัมน์รูปแบบ AMSS (ประเภท,ชั้น,ปี,รหัส, คะแนน...) — คีย์วิชาของเรา => index ในแถว
        // O-NET ไฟล์ AMSS เป็น 8 วิชา (ไทย4 คณิต5 วิทย์6 สังคม7 อังกฤษ8 สุข9 ศิลปะ10 การงาน11) → หยิบ 4 วิชาปัจจุบัน
        $amssCols = [
            'onet' => ['thai' => 4, 'math' => 5, 'science' => 6, 'english' => 8],
            'nt' => ['lang' => 4, 'calc' => 5],
            'rt' => ['aloud' => 4, 'comprehension' => 5],
            'last' => ['thai' => 4, 'math' => 5, 'science' => 6, 'english' => 7],
        ][$v['type']] ?? [];
        $maxAmssCol = $amssCols ? max($amssCols) : 0; // คอลัมน์คะแนนสูงสุดที่ต้องมีในรูปแบบ AMSS

        $rows = array_map('str_getcsv', file($request->file('file')->getRealPath(), FILE_SKIP_EMPTY_LINES));
        $imported = 0;
        $skipped = 0;
        foreach ($rows as $cols) {
            $cols[0] = trim(str_replace("\xEF\xBB\xBF", '', $cols[0] ?? ''));
            // ตรวจรูปแบบ: AMSS = คอลัมน์แรกเป็นเลขประเภทเล็กๆ (<100) + คอลัมน์ที่ 4 เป็นรหัสโรงเรียน
            // ต้องมีคอลัมน์ครบถึงคอลัมน์คะแนนสุดท้าย (กันแถวสั้นทำคะแนนหายเงียบ)
            $isAmss = count($cols) > $maxAmssCol && is_numeric($cols[0]) && (int) $cols[0] < 100 && is_numeric(trim($cols[3] ?? ''));
            $code = $isAmss ? trim($cols[3]) : $cols[0];
            if (! ctype_digit($code)) {
                continue; // ข้ามหัวตาราง/แถวเสีย
            }
            $unitId = $unitByCode[$code] ?? null;
            if (! $unitId) {
                $skipped++;

                continue;
            }
            foreach ($subjects as $idx => $subject) {
                $col = $isAmss ? ($amssCols[$subject] ?? null) : ($idx + 1);
                $raw = $col === null ? '' : trim($cols[$col] ?? '');
                $score = ($raw === '' || ! is_numeric($raw)) ? null : min(100, max(0, (float) $raw));
                AchievementResult::updateOrCreate(
                    ['unit_id' => $unitId, 'test_type' => $v['type'], 'academic_year' => $v['year'], 'grade' => $v['grade'], 'subject' => $subject],
                    ['score' => $score, 'created_by' => $request->user()->id],
                );
            }
            $imported++;
        }

        return back()->with('success', "นำเข้าคะแนน {$imported} โรงเรียน".($skipped ? " · ข้าม {$skipped} (รหัสไม่ตรง/ไม่มีสิทธิ์)" : ''));
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'type' => ['required', Rule::in(array_keys(AchievementResult::TEST_TYPES))],
            'year' => ['required', 'integer', 'min:2400', 'max:2700'],
            'grade' => ['required', 'string', 'max:10'],
            'results' => ['present', 'array'],
            'results.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'results.*.scores' => ['array'],
            'results.*.scores.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $subjects = array_keys(AchievementResult::SUBJECTS[$v['type']]);
        $overseer = $this->isOverseer($request);
        $myUnit = $request->user()->unit_id;

        foreach ($v['results'] as $row) {
            // โรงเรียนทั่วไปบันทึกได้เฉพาะหน่วยงานตน
            if (! $overseer && (int) $row['unit_id'] !== (int) $myUnit) {
                continue;
            }
            foreach ($subjects as $subject) {
                $score = $row['scores'][$subject] ?? null;
                AchievementResult::updateOrCreate(
                    [
                        'unit_id' => $row['unit_id'],
                        'test_type' => $v['type'],
                        'academic_year' => $v['year'],
                        'grade' => $v['grade'],
                        'subject' => $subject,
                    ],
                    ['score' => $score === '' ? null : $score, 'created_by' => $request->user()->id],
                );
            }
        }

        return back()->with('success', 'บันทึกผลสัมฤทธิ์เรียบร้อยแล้ว');
    }
}
