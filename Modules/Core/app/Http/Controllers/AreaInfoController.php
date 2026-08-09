<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Student;
use Modules\Core\Models\Unit;

/**
 * รายงานสารสนเทศเขต (EMIS) — ภาพรวมข้อมูลโรงเรียนทั้งเขต
 * รวมข้อมูลที่มีอยู่แล้ว: โรงเรียน + นักเรียน + บุคลากร
 */
class AreaInfoController extends Controller
{
    /** จัดขนาดโรงเรียนตามจำนวนนักเรียน (เกณฑ์ สพฐ.) */
    private function sizeLabel(int $n): string
    {
        return match (true) {
            $n <= 120 => 'เล็ก',
            $n <= 719 => 'กลาง',
            $n <= 1679 => 'ใหญ่',
            default => 'ใหญ่พิเศษ',
        };
    }

    public function index(): Response
    {
        $schools = Unit::schools()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $schoolIds = $schools->pluck('id');

        // นับนักเรียน (กำลังเรียน) และบุคลากร แยกตามหน่วยงาน — query ครั้งเดียว
        $studentByUnit = Student::where('status', 'studying')
            ->selectRaw('unit_id, count(*) as c')->groupBy('unit_id')->pluck('c', 'unit_id');
        $staffByUnit = User::whereNotNull('unit_id')
            ->selectRaw('unit_id, count(*) as c')->groupBy('unit_id')->pluck('c', 'unit_id');

        $rows = $schools->map(function (Unit $s) use ($studentByUnit, $staffByUnit) {
            $students = (int) ($studentByUnit[$s->id] ?? 0);

            return [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'students' => $students,
                'staff' => (int) ($staffByUnit[$s->id] ?? 0),
                'size' => $this->sizeLabel($students),
            ];
        });

        $totalStudents = (int) Student::where('status', 'studying')->count();

        // นักเรียนแยกชั้น (เรียงตามลำดับชั้นจริง)
        $byGradeRaw = Student::where('status', 'studying')
            ->selectRaw('grade, count(*) as c')->groupBy('grade')->pluck('c', 'grade');
        $byGrade = collect(Student::GRADES)
            ->map(fn ($g) => ['grade' => $g, 'count' => (int) ($byGradeRaw[$g] ?? 0)])
            ->filter(fn ($r) => $r['count'] > 0)->values();

        // ขนาดโรงเรียน
        $sizeOrder = ['เล็ก', 'กลาง', 'ใหญ่', 'ใหญ่พิเศษ'];
        $bySizeRaw = $rows->groupBy('size')->map->count();
        $bySize = collect($sizeOrder)->map(fn ($s) => ['size' => $s, 'count' => (int) ($bySizeRaw[$s] ?? 0)]);

        return Inertia::render('Core::AreaInfo', [
            'summary' => [
                'schools' => $schools->count(),
                'students' => $totalStudents,
                'staff' => (int) User::whereIn('unit_id', $schoolIds)->count(),
                'avgPerSchool' => $schools->count() ? (int) round($totalStudents / $schools->count()) : 0,
            ],
            'byGrade' => $byGrade,
            'byGender' => [
                'M' => (int) Student::where('status', 'studying')->where('gender', 'M')->count(),
                'F' => (int) Student::where('status', 'studying')->where('gender', 'F')->count(),
            ],
            'bySize' => $bySize,
            'schools' => $rows,
        ]);
    }
}
