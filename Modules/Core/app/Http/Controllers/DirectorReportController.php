<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Attendance\Models\Attendance;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveRequestRoute;
use Modules\Leave\Models\LeaveType;
use Modules\Saraban\Models\Document;

/**
 * รายงานสำหรับผู้บริหาร (ผอ./รองผอ./เลขาฯ)
 * รายงานการลงเวลา · สถิติการลา · ทะเบียนหนังสือ · สมุดโทรศัพท์
 */
class DirectorReportController extends Controller
{
    /** หน่วยงานที่จำกัดการมองเห็น — admin/area_admin = ทั้งหมด (null), อื่น ๆ = หน่วยงานตน */
    private function unitScope(Request $request): ?int
    {
        $u = $request->user();

        return $u->hasAnyRole(['admin', 'area_admin']) ? null : $u->unit_id;
    }

    /** รายงานการลงเวลา — เลือกวันที่ได้ (ลงเวลาแล้ว / ยังไม่ลงเวลา) */
    public function attendance(Request $request): Response
    {
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $scope = $this->unitScope($request);
        $records = Attendance::with(['user:id,name,position_id', 'user.position:id,name'])
            ->whereDate('date', $date)
            ->when($scope, fn ($q) => $q->whereHas('user', fn ($w) => $w->where('unit_id', $scope)))
            ->get();

        // ลงเวลาแล้ว = สถานะ present/late เท่านั้น
        $clocked = $records->whereIn('status', Attendance::CLOCKED_STATUSES);
        $present = $clocked
            ->sortBy('check_in_time')
            ->map(fn (Attendance $a) => [
                'name' => $a->user?->name,
                'position' => $a->user?->position?->name,
                'check_in_time' => $a->check_in_time,
                'check_out_time' => $a->check_out_time,
                'status' => $a->status,
            ])->values();

        $clockedIds = $clocked->pluck('user_id')->all();
        $recordByUser = $records->keyBy('user_id');
        $absent = User::with('position:id,name')
            ->whereNotIn('id', $clockedIds ?: [0])
            ->when($scope, fn ($q) => $q->where('unit_id', $scope))
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'name' => $u->name,
                'position' => $u->position?->name,
                // สถานะที่เจ้าหน้าที่บันทึกไว้ (ลา/ไปราชการ/ขาด/ลืมลงเวลา) ถ้ามี
                'status' => $recordByUser[$u->id]->status ?? null,
                'status_label' => isset($recordByUser[$u->id]) ? (Attendance::STATUS_LABELS[$recordByUser[$u->id]->status] ?? null) : null,
                'note' => $recordByUser[$u->id]->note ?? null,
            ]);

        return Inertia::render('Core::Reports/Attendance', [
            'date' => $date->toDateString(),
            'present' => $present,
            'absent' => $absent,
            'summary' => [
                'total' => User::when($scope, fn ($q) => $q->where('unit_id', $scope))->count(),
                'present' => $clocked->where('status', 'present')->count(),
                'late' => $clocked->where('status', 'late')->count(),
                'absent' => $absent->count(),
            ],
        ]);
    }

    /** บัญชีลงเวลาปฏิบัติงาน — สรุปการมาทำงานรายเดือน รายบุคคล */
    public function attendanceLedger(Request $request): Response
    {
        $month = $request->input('month')
            ? Carbon::parse($request->input('month').'-01')
            : Carbon::today()->startOfMonth();

        $scope = $this->unitScope($request);
        $records = Attendance::whereBetween('date', [
            $month->copy()->startOfMonth()->toDateString(),
            $month->copy()->endOfMonth()->toDateString(),
        ])->get()->groupBy('user_id');

        $people = User::with('position:id,name')
            ->when($scope, fn ($q) => $q->where('unit_id', $scope))
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($records) {
                $rows = $records->get($u->id) ?? collect();

                return [
                    'name' => $u->name,
                    'position' => $u->position?->name,
                    'present' => $rows->where('status', 'present')->count(),
                    'late' => $rows->where('status', 'late')->count(),
                    'leave' => $rows->whereIn('status', ['leave', 'official'])->count(),
                    'absent' => $rows->where('status', 'absent')->count(),
                    'worked' => $rows->whereIn('status', ['present', 'late'])->count(),
                ];
            });

        return Inertia::render('Core::Reports/AttendanceLedger', [
            'month' => $month->format('Y-m'),
            'people' => $people,
        ]);
    }

    /** สถิติการลา — สรุปตามประเภท + สถานะ + ผู้ที่ลา/ไปราชการวันนี้ */
    public function leaveStatistics(Request $request): Response
    {
        // ปีงบประมาณ (พ.ศ.) — 1 ต.ค. (ปีก่อน) ถึง 30 ก.ย.
        $today = Carbon::today();
        $defaultBe = ($today->month >= 10 ? $today->year + 1 : $today->year) + 543;
        $year = (int) ($request->input('year') ?: $defaultBe);
        // กันค่าเก่าที่เป็น ค.ศ. (เช่น 2026) — แปลงเป็น พ.ศ. อัตโนมัติ
        if ($year < 2500) {
            $year += 543;
        }
        $ceEnd = $year - 543;

        // ช่วงเวลา: ทั้งปี / ครึ่งปีแรก (ครั้งที่1) / ครึ่งปีหลัง (ครั้งที่2)
        $period = in_array($request->input('period'), ['h1', 'h2'], true) ? $request->input('period') : 'all';
        [$rangeStart, $rangeEnd] = match ($period) {
            'h1' => [Carbon::create($ceEnd - 1, 10, 1)->startOfDay(), Carbon::create($ceEnd, 3, 31)->endOfDay()],
            'h2' => [Carbon::create($ceEnd, 4, 1)->startOfDay(), Carbon::create($ceEnd, 9, 30)->endOfDay()],
            default => [Carbon::create($ceEnd - 1, 10, 1)->startOfDay(), Carbon::create($ceEnd, 9, 30)->endOfDay()],
        };

        // จำกัดการมองเห็นตามหน่วยงาน (เว้น admin/area_admin)
        $scope = $this->unitScope($request);
        $byUnit = fn ($q) => $scope ? $q->whereHas('user', fn ($w) => $w->where('unit_id', $scope)) : $q;

        $summary = [
            // รอฉันอนุมัติ = ใบลาที่ส่งมาถึงคิวของผู้ใช้คนนี้ (ตรงกับหน้าตรวจสอบวันลา)
            'my_pending' => LeaveRequestRoute::where('approver_id', $request->user()->id)
                ->where('status', 'pending')->count(),
            'approved' => $byUnit(LeaveRequest::whereBetween('start_date', [$rangeStart, $rangeEnd])->where('status', 'approved'))->count(),
            'rejected' => $byUnit(LeaveRequest::whereBetween('start_date', [$rangeStart, $rangeEnd])->where('status', 'rejected'))->count(),
        ];

        // เฉพาะประเภท "การลา" จริง — ตัด ขออนุญาตไปราชการ / ลาพักผ่อน ออก (ให้ตรงระบบเก่า)
        $types = LeaveType::where('name', 'not like', '%ราชการ%')
            ->where('name', 'not like', '%พักผ่อน%')
            ->orderBy('id')
            ->get(['id', 'name']);

        // รวมจำนวนครั้ง/วันลา (อนุมัติแล้ว) ในช่วงที่เลือก แยกราย user + ประเภท
        $agg = $byUnit(LeaveRequest::where('status', 'approved')
            ->whereBetween('start_date', [$rangeStart, $rangeEnd]))
            ->selectRaw('user_id, leave_type_id, count(*) as times, sum(total_days) as days')
            ->groupBy('user_id', 'leave_type_id')
            ->get()
            ->groupBy('user_id');

        // สถิติรายคน (เรียงตามชื่อ — เหมือนทะเบียนระบบเก่า)
        $people = User::with(['group:id,name', 'department:id,name', 'position:id,name'])
            ->when($scope, fn ($q) => $q->where('unit_id', $scope))
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($types, $agg) {
                $rows = $agg->get($u->id) ?? collect();
                $byType = [];

                foreach ($types as $t) {
                    $r = $rows->firstWhere('leave_type_id', $t->id);
                    $byType[$t->id] = ['times' => $r ? (int) $r->times : 0, 'days' => $r ? (float) $r->days : 0.0];
                }

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'position' => $u->position?->name,
                    'group' => $u->group?->name ?? $u->department?->name,
                    'byType' => $byType,
                ];
            });

        $today = Carbon::today()->toDateString();
        $onLeaveToday = $byUnit(LeaveRequest::with(['user:id,name', 'leaveType:id,name'])
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today))
            ->get()
            ->map(fn (LeaveRequest $l) => [
                'name' => $l->user?->name,
                'type' => $l->leaveType?->name,
                'start' => $l->start_date?->toDateString(),
                'end' => $l->end_date?->toDateString(),
            ]);

        // รายการใบลา อนุมัติแล้ว / ไม่อนุมัติ (ทั้งระบบ) — สำหรับกดดูจากการ์ด
        $listFor = fn (string $status) => $byUnit(LeaveRequest::with(['user:id,name', 'leaveType:id,name'])
            ->whereBetween('start_date', [$rangeStart, $rangeEnd])
            ->where('status', $status))
            ->latest('start_date')
            ->limit(200)
            ->get()
            ->map(fn (LeaveRequest $l) => [
                'name' => $l->user?->name,
                'type' => $l->leaveType?->name,
                'reason' => $l->reason,
                'start' => $l->start_date?->format('d/m/Y'),
                'end' => $l->end_date?->format('d/m/Y'),
                'days' => $l->total_days,
            ])->values();

        return Inertia::render('Core::Reports/LeaveStatistics', [
            'year' => $year,
            'period' => $period,
            'summary' => $summary,
            'types' => $types->map(fn (LeaveType $t) => ['id' => $t->id, 'name' => $t->name])->values(),
            'people' => $people,
            'onLeaveToday' => $onLeaveToday,
            'approvedList' => $listFor('approved'),
            'rejectedList' => $listFor('rejected'),
        ]);
    }

    /** ทะเบียนหนังสือ — รายการเอกสารทั้งหมด กรองตามประเภทได้ */
    public function documents(Request $request): Response
    {
        $category = $request->input('category');

        $scope = $this->unitScope($request);
        $query = Document::with('creator:id,name')
            ->when($scope, fn ($q) => $q->whereHas('creator', fn ($w) => $w->where('unit_id', $scope)))
            ->latest();
        if ($category && array_key_exists($category, Document::CATEGORIES)) {
            $query->where('category', $category);
        }

        $documents = $query->limit(300)->get()->map(fn (Document $d) => [
            'id' => $d->id,
            'number' => $d->document_number,
            'category_label' => Document::CATEGORIES[$d->category] ?? $d->category,
            'title' => $d->title,
            'content' => $d->content,
            'creator' => $d->creator?->name,
            'status' => $d->status,
            'file_url' => $d->file_path ? asset('storage/'.$d->file_path) : null,
            'created_at' => $d->created_at->format('d/m/Y'),
        ]);

        return Inertia::render('Core::Reports/Documents', [
            'documents' => $documents,
            'categories' => collect(Document::CATEGORIES)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'current' => $category,
        ]);
    }

    /** สมุดโทรศัพท์ — ไดเรกทอรีบุคลากร */
    public function phoneBook(Request $request): Response
    {
        $scope = $this->unitScope($request);
        $users = User::with(['position:id,name', 'group:id,name', 'department:id,name'])
            ->when($scope, fn ($q) => $q->where('unit_id', $scope))
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'name' => $u->name,
                'position' => $u->position?->name,
                'group' => $u->group?->name ?? $u->department?->name,
                'phone' => $u->phone,
                'email' => $u->email,
            ]);

        return Inertia::render('Core::Reports/PhoneBook', [
            'users' => $users,
        ]);
    }
}
