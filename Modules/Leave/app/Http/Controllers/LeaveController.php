<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\Signature;
use Modules\Core\Notifications\ApprovalNotification;
use Modules\Leave\Models\LeaveBalance;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveRequestRoute;
use Modules\Leave\Models\LeaveType;
use Modules\Leave\Services\LeaveWorkflowService;
use RuntimeException;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveWorkflowService $workflow)
    {
    }

    /**
     * หน้าแรกระบบการลา — สถิติการลาปีงบประมาณนี้ + ลิงก์เขียนคำขอแยกประเภท
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        [$fyStart, $fyEnd, $fyBe] = $this->fiscalYear();

        $types = LeaveType::where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'default_days']);

        // สถิติการลาในปีงบประมาณนี้ (เฉพาะที่อนุมัติแล้ว) — ครั้ง / วัน
        $approved = LeaveRequest::where('user_id', $user->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereBetween('start_date', [$fyStart, $fyEnd])
            ->get(['leave_type_id', 'total_days']);

        $stats = $types->map(fn (LeaveType $t) => [
            'code' => $t->code,
            'name' => $t->name,
            'times' => $approved->where('leave_type_id', $t->id)->count(),
            'days' => (float) $approved->where('leave_type_id', $t->id)->sum('total_days'),
        ]);

        return Inertia::render('Leave::Index', [
            'fiscalYear' => $fyBe,
            'stats' => $stats,
            'leaveTypes' => $types->map(fn (LeaveType $t) => [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
            ])->values(),
        ]);
    }

    /**
     * แบบฟอร์มเขียนคำขอลา (แยกตามประเภท)
     */
    public function create(Request $request): Response
    {
        $code = $request->query('type', 'sick');
        $type = LeaveType::where('code', $code)->where('is_active', true)->firstOrFail();

        // เพื่อนร่วมหน่วยงาน (สำหรับเลือกผู้รับมอบงาน) — AMSS ส่วน 9
        $colleagues = User::where('unit_id', $request->user()->unit_id)
            ->where('id', '!=', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('Leave::Create', [
            'leaveType' => ['id' => $type->id, 'code' => $type->code, 'name' => $type->name],
            'colleagues' => $colleagues,
        ]);
    }

    /**
     * บันทึกคำขอลา (สถานะร่าง = รอเสนอแฟ้ม) แล้วไปยังแฟ้มการลา
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'written_at' => ['nullable', 'string', 'max:255'],
            'written_date' => ['nullable', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'handover_to' => ['nullable', 'integer', 'exists:users,id'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar', 'max:10240'],
        ]);

        // ผู้รับมอบงานต้องเป็นบุคลากรในหน่วยงานเดียวกัน และไม่ใช่ตัวเอง
        $handoverTo = null;
        if (! empty($validated['handover_to']) && (int) $validated['handover_to'] !== $request->user()->id) {
            $handoverTo = User::where('id', $validated['handover_to'])
                ->where('unit_id', $request->user()->unit_id)
                ->value('id');
        }

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $totalDays = $start->diffInDays($end) + 1;

        $type = LeaveType::findOrFail($validated['leave_type_id']);

        $leave = new LeaveRequest([
            'user_id' => $request->user()->id,
            'leave_type_id' => $type->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'written_at' => $validated['written_at'] ?? null,
            'written_date' => $validated['written_date'] ?? now()->toDateString(),
            'contact_address' => $validated['contact_address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'handover_to' => $handoverTo,
            'status' => LeaveRequest::STATUS_DRAFT,
        ]);

        if ($request->hasFile('file')) {
            $leave->file_path = $request->file('file')->store('leaves', 'public');
        }

        $leave->save();

        return redirect()
            ->route('leave.requests.folder')
            ->with('success', 'บันทึกคำขอลาแล้ว กรุณากด "เสนอแฟ้ม" เพื่อส่งให้ผู้บริหารพิจารณา');
    }

    /**
     * แฟ้มการลา — คำขอลาที่ยัง "รอดำเนินการ" (ร่าง/รออนุมัติ/ตีกลับ)
     */
    public function folder(Request $request): Response
    {
        return Inertia::render('Leave::Folder', [
            'requests' => $this->requestRows($request->user()->id, [
                LeaveRequest::STATUS_DRAFT,
                LeaveRequest::STATUS_PENDING,
                LeaveRequest::STATUS_REJECTED,
            ]),
            'title' => 'แฟ้มการลา',
            'navLinks' => [
                ['routeName' => 'leave.requests.history', 'label' => 'ประวัติการลา →'],
            ],
        ]);
    }

    /**
     * ประวัติการลา — คำขอที่อนุมัติแล้ว (เสร็จสมบูรณ์)
     */
    public function history(Request $request): Response
    {
        return Inertia::render('Leave::Folder', [
            'requests' => $this->requestRows($request->user()->id, [LeaveRequest::STATUS_APPROVED]),
            'title' => 'ประวัติการลา',
            'navLinks' => [
                ['routeName' => 'leave.requests.folder', 'label' => '← แฟ้มการลา'],
                ['routeName' => 'leave.requests.cancelled', 'label' => 'แฟ้มยกเลิกวันลา →'],
            ],
        ]);
    }

    /**
     * แฟ้มยกเลิกวันลา — คำขอที่ถูกยกเลิก
     */
    public function cancelled(Request $request): Response
    {
        return Inertia::render('Leave::Folder', [
            'requests' => $this->requestRows($request->user()->id, [LeaveRequest::STATUS_CANCELLED]),
            'title' => 'แฟ้มยกเลิกวันลา',
            'navLinks' => [
                ['routeName' => 'leave.requests.history', 'label' => '← ประวัติการลา'],
            ],
        ]);
    }

    /**
     * แถวคำขอลาของผู้ใช้ ตามสถานะที่กำหนด
     *
     * @param  array<int, string>  $statuses
     */
    private function requestRows(int $userId, array $statuses): \Illuminate\Support\Collection
    {
        return LeaveRequest::with(['user:id,name', 'leaveType:id,name', 'routes.approver:id,name'])
            ->where('user_id', $userId)
            ->whereIn('status', $statuses)
            ->latest()
            ->get()
            ->map(function (LeaveRequest $r) {
                $passed = $r->routes->firstWhere('status', LeaveRequestRoute::STATUS_APPROVED);

                return [
                    'id' => $r->id,
                    'fiscal_year' => $r->start_date->month >= 10 ? $r->start_date->year + 544 : $r->start_date->year + 543,
                    'subject' => 'ขอ'.($r->leaveType?->name ?? ''),
                    'sender' => $r->user?->name,
                    'sent_thai' => $this->thaiDateTime($r->created_at),
                    'passed_by' => $passed?->approver?->name,
                    'status' => $r->status,
                    'can_propose' => $r->status === LeaveRequest::STATUS_DRAFT,
                    'can_cancel' => in_array($r->status, [LeaveRequest::STATUS_DRAFT, LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED], true),
                ];
            });
    }

    /**
     * เสนอแฟ้ม — แสดงบันทึกขออนุญาตลา (รูปแบบราชการ) + สถิติ + ปุ่มยืนยันเสนอ
     */
    public function proposal(Request $request, LeaveRequest $leaveRequest): Response
    {
        $uid = $request->user()->id;
        $leaveRequest->load(['user:id,name,unit_id', 'leaveType:id,name', 'routes.approver:id,name']);

        // ขั้นที่ "ฉัน" ต้องดำเนินการอยู่ตอนนี้
        $myRoute = $leaveRequest->routes->first(
            fn (LeaveRequestRoute $r) => $r->approver_id === $uid && $r->status === LeaveRequestRoute::STATUS_PENDING,
        );

        // เจ้าของ / ผู้อยู่ในเส้นทาง / ผู้กำกับดูแล (จนท.วันลา เลขาฯ ผู้บริหาร) ดูได้
        $me = $request->user();
        $canOversee = $me->hasAnyRole(['admin', 'area_admin'])
            || ($me->hasAnyRole(['leave_officer', 'secretary', 'director', 'deputy_director']) && $leaveRequest->user?->unit_id === $me->unit_id);
        abort_unless(
            $leaveRequest->user_id === $uid || $canOversee || $myRoute,
            403,
        );

        // เปิดใบลา = เคลียร์แจ้งเตือนกระดิ่งของใบนี้ (เฉพาะคนที่ต้องดำเนินการ)
        if ($myRoute) {
            $request->user()->unreadNotifications->each(function ($n) use ($leaveRequest) {
                if (($n->data['key'] ?? null) === 'leave:'.$leaveRequest->id) {
                    $n->markAsRead();
                }
            });
        }

        [$fyStart, $fyEnd, $fyBe] = $this->fiscalYear($leaveRequest->start_date);

        // ลายเซ็น (ผู้ขอ + ผู้ดำเนินการในเส้นทาง) + รายชื่อเจ้าหน้าที่วันลา
        $sigIds = $leaveRequest->routes->pluck('approver_id')->push($leaveRequest->user_id)->unique();
        $signatures = Signature::whereIn('user_id', $sigIds)->pluck('file_path', 'user_id');
        $officerIds = User::whereHas('roles', fn ($q) => $q->where('name', 'leave_officer'))->pluck('id');

        // สถิติแยกประเภท: ลามาแล้ว / ครั้งนี้ / รวม
        $types = LeaveType::where('is_active', true)->orderBy('id')->get(['id', 'name']);
        $approved = LeaveRequest::where('user_id', $leaveRequest->user_id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereBetween('start_date', [$fyStart, $fyEnd])
            ->where('id', '!=', $leaveRequest->id)
            ->get(['leave_type_id', 'total_days']);

        $stats = $types->map(function (LeaveType $t) use ($approved, $leaveRequest) {
            $taken = (float) $approved->where('leave_type_id', $t->id)->sum('total_days');
            $thisTime = $t->id === $leaveRequest->leave_type_id ? (float) $leaveRequest->total_days : 0.0;

            return ['name' => $t->name, 'taken' => $taken, 'this_time' => $thisTime, 'total' => $taken + $thisTime];
        });

        // บทบาทของฉันต่อใบนี้: เจ้าหน้าที่วันลา (เสนอต่อ) หรือ ผู้อนุญาต (สั่งการ)
        $myRole = null;
        if ($myRoute) {
            $myRole = $request->user()->hasRole('leave_officer') && $officerIds->contains($uid) ? 'officer' : 'approver';
        }

        // ตัวเลือกผู้อนุญาต (สำหรับเจ้าหน้าที่วันลา)
        $approverOptions = $myRole === 'officer'
            ? User::whereHas('roles', fn ($q) => $q->whereIn('name', ['executive']))
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])->values()
            : [];

        // ไทม์ไลน์ที่ดำเนินการแล้ว (ผู้ตรวจสอบ / ผู้อนุญาต)
        $acted = $leaveRequest->routes
            ->filter(fn (LeaveRequestRoute $r) => in_array($r->status, [LeaveRequestRoute::STATUS_APPROVED, LeaveRequestRoute::STATUS_REJECTED], true))
            ->map(function (LeaveRequestRoute $r) use ($signatures, $officerIds) {
                $parsed = $this->parseComment($r->comment);

                return [
                    'role_label' => $officerIds->contains($r->approver_id) ? 'ผู้ตรวจสอบ (เจ้าหน้าที่วันลา)' : 'ผู้อนุญาต',
                    'approver' => $r->approver?->name,
                    'status' => $r->status,
                    'actions' => $parsed['actions'],
                    'note' => $parsed['note'],
                    'acted_thai' => $r->acted_at ? $r->acted_at->locale('th')->translatedFormat('j M').' '.($r->acted_at->year + 543) : null,
                    'signature_url' => isset($signatures[$r->approver_id]) ? asset('storage/'.$signatures[$r->approver_id]) : null,
                ];
            })->values();

        return Inertia::render('Leave::Proposal', [
            'leave' => [
                'id' => $leaveRequest->id,
                'requester' => $leaveRequest->user->name,
                'requester_position' => $this->roleLabel($leaveRequest->user),
                'type' => $leaveRequest->leaveType?->name,
                'subject' => 'ขอ'.($leaveRequest->leaveType?->name ?? ''),
                'reason' => $leaveRequest->reason,
                'written_at' => $leaveRequest->written_at,
                'written_thai' => $this->thaiDate($leaveRequest->written_date ?? $leaveRequest->created_at),
                'start_thai' => $this->thaiDate($leaveRequest->start_date),
                'end_thai' => $this->thaiDate($leaveRequest->end_date),
                'total_days' => $this->thaiNum((string) (int) $leaveRequest->total_days),
                'contact_address' => $leaveRequest->contact_address,
                'phone' => $leaveRequest->phone ? $this->thaiNum($leaveRequest->phone) : null,
                'signature_url' => isset($signatures[$leaveRequest->user_id]) ? asset('storage/'.$signatures[$leaveRequest->user_id]) : null,
                'status' => $leaveRequest->status,
            ],
            'fiscalYear' => $fyBe,
            'fiscalYears' => [$fyBe, $fyBe + 1],
            'stats' => $stats,
            'acted' => $acted,
            'canSubmit' => $leaveRequest->status === LeaveRequest::STATUS_DRAFT && $leaveRequest->user_id === $uid,
            'myRole' => $myRole,
            'myRouteId' => $myRoute?->id,
            'approverOptions' => $approverOptions,
            // ปุ่มย้อนกลับ — ขึ้นกับว่ามาจากไหน (ทะเบียนลา / แฟ้มตรวจสอบ / แฟ้มการลา)
            'back' => $request->input('from') === 'registry'
                ? ['url' => route('leave.registry.show', $leaveRequest->user_id), 'label' => 'กลับทะเบียนลา']
                : ['url' => $myRole ? route('leave.requests.inbox') : route('leave.requests.folder'), 'label' => $myRole ? 'กลับแฟ้มตรวจสอบ' : 'กลับแฟ้มการลา'],
        ]);
    }

    /**
     * เจ้าหน้าที่วันลาเสนอต่อผู้อนุญาต
     */
    public function forward(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $validated = $request->validate([
            'approver_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $uid = $request->user()->id;
        $officerRoute = $leaveRequest->routes()
            ->where('approver_id', $uid)
            ->where('status', LeaveRequestRoute::STATUS_PENDING)
            ->first();

        abort_unless($officerRoute && $request->user()->hasRole('leave_officer'), 403);

        $approver = User::findOrFail($validated['approver_id']);
        $this->workflow->forwardToApprover($leaveRequest, $officerRoute, $approver);

        return redirect()->route('leave.requests.inbox')->with('success', 'เสนอผู้อนุญาตเรียบร้อยแล้ว');
    }

    /**
     * ยืนยันเสนอแฟ้ม — ส่งคำขอลาเข้าสู่เส้นทางอนุมัติ
     */
    public function submit(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->user_id === $request->user()->id, 403);
        abort_unless($leaveRequest->status === LeaveRequest::STATUS_DRAFT, 400);

        // ตรวจวันลาคงเหลือ (เฉพาะประเภทที่มีโควต้า)
        $type = $leaveRequest->leaveType;
        if ($type && $type->default_days > 0) {
            // ใช้ปีงบประมาณ พ.ศ. ให้ตรงกับสถิติ/ทะเบียน (1 ต.ค.–30 ก.ย.)
            [, , $year] = $this->fiscalYear($leaveRequest->start_date);
            $balance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('leave_type_id', $type->id)
                ->where('year', $year)
                ->first();
            $remaining = $balance ? $balance->remaining() : (float) $type->default_days;

            if ($leaveRequest->total_days > $remaining) {
                return back()->with('error', "วันลาคงเหลือไม่พอ (เหลือ {$remaining} วัน แต่ขอลา {$leaveRequest->total_days} วัน)");
            }
        }

        try {
            $this->workflow->submit($leaveRequest);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // แจ้งผู้รับมอบงานให้ยืนยัน "รับมอบงาน" (AMSS ส่วน 9)
        if ($leaveRequest->handover_to) {
            User::find($leaveRequest->handover_to)?->notify(new ApprovalNotification(
                title: 'มีงานมอบหมายให้ปฏิบัติหน้าที่แทน',
                message: $request->user()->name.' ลา '.($leaveRequest->leaveType?->name ?? '').' '.$leaveRequest->start_date->format('d/m/Y').' — โปรดยืนยันรับมอบงาน',
                url: route('leave.handover.inbox'),
                type: 'info',
                key: 'leave-handover:'.$leaveRequest->id,
            ));
        }

        return redirect()
            ->route('leave.requests.folder')
            ->with('success', 'เสนอแฟ้มเรียบร้อยแล้ว — ส่งให้ผู้บริหารพิจารณา'
                .($leaveRequest->handover_to ? ' และแจ้งผู้รับมอบงานแล้ว' : ''));
    }

    /** แฟ้มงานที่ถูกมอบให้ฉันปฏิบัติแทน (รับมอบงาน) */
    public function handoverInbox(Request $request): Response
    {
        $rows = LeaveRequest::with(['user:id,name', 'leaveType:id,name'])
            ->where('handover_to', $request->user()->id)
            ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
            ->latest()
            ->get()
            ->map(fn (LeaveRequest $r) => [
                'id' => $r->id,
                'requester' => $r->user?->name,
                'type' => $r->leaveType?->name,
                'period_thai' => $this->thaiDate($r->start_date).' – '.$this->thaiDate($r->end_date),
                'total_days' => $r->total_days,
                'status' => $r->status,
                'accepted' => (bool) $r->handover_accepted_at,
                'accepted_thai' => $r->handover_accepted_at ? $this->thaiDate($r->handover_accepted_at) : null,
            ]);

        return Inertia::render('Leave::HandoverInbox', ['rows' => $rows]);
    }

    /** ยืนยันรับมอบงาน */
    public function acceptHandover(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->handover_to === $request->user()->id, 403);
        if (! $leaveRequest->handover_accepted_at) {
            $leaveRequest->update(['handover_accepted_at' => now()]);
            // แจ้งกลับผู้ลาว่าผู้รับมอบงานยืนยันแล้ว
            $leaveRequest->user?->notify(new ApprovalNotification(
                title: 'ผู้รับมอบงานยืนยันแล้ว',
                message: $request->user()->name.' รับมอบงานช่วงที่ท่านลาเรียบร้อยแล้ว',
                url: route('leave.requests.show', $leaveRequest->id),
                type: 'success',
                key: 'leave-handover-ack:'.$leaveRequest->id,
            ));
        }

        return back()->with('success', 'ยืนยันรับมอบงานเรียบร้อย');
    }

    /**
     * รายละเอียดใบลา + ไทม์ไลน์การอนุมัติ
     */
    public function show(LeaveRequest $leaveRequest): Response
    {
        $this->authorizeView($leaveRequest);

        $leaveRequest->load(['user:id,name', 'leaveType:id,name', 'routes.approver:id,name']);

        return Inertia::render('Leave::Show', [
            'leave' => [
                'id' => $leaveRequest->id,
                'type' => $leaveRequest->leaveType?->name,
                'requester' => $leaveRequest->user->name,
                'start_date' => $leaveRequest->start_date->format('d/m/Y'),
                'end_date' => $leaveRequest->end_date->format('d/m/Y'),
                'total_days' => $leaveRequest->total_days,
                'reason' => $leaveRequest->reason,
                'file_path' => $leaveRequest->file_path,
                'status' => $leaveRequest->status,
                'routes' => $leaveRequest->routes->map(fn (LeaveRequestRoute $r) => [
                    'step_order' => $r->step_order,
                    'approver' => $r->approver->name,
                    'status' => $r->status,
                    'comment' => $r->comment,
                    'acted_at' => $r->acted_at?->format('Y-m-d H:i'),
                ]),
            ],
        ]);
    }

    /**
     * แฟ้มตรวจสอบวันลา — ใบลาที่รอ "ฉัน" อนุมัติ
     */
    public function inbox(Request $request): Response
    {
        $isOfficer = $request->user()->hasRole('leave_officer');

        $rows = LeaveRequestRoute::with(['leaveRequest.user:id,name', 'leaveRequest.leaveType:id,name'])
            ->where('approver_id', $request->user()->id)
            ->where('status', LeaveRequestRoute::STATUS_PENDING)
            ->latest()
            ->get()
            ->filter(fn (LeaveRequestRoute $r) => $r->leaveRequest)
            ->map(fn (LeaveRequestRoute $r) => [
                'id' => $r->leave_request_id,
                'fiscal_year' => $r->leaveRequest->start_date->month >= 10 ? $r->leaveRequest->start_date->year + 544 : $r->leaveRequest->start_date->year + 543,
                'subject' => 'ขอ'.($r->leaveRequest->leaveType?->name ?? ''),
                'sender' => $r->leaveRequest->user?->name,
                'sent_thai' => $this->thaiDateTime($r->leaveRequest->created_at),
                'status' => $isOfficer ? 'เจ้าหน้าที่วันลา' : 'เสนอผู้บริหาร',
            ])->values();

        return Inertia::render('Leave::Inbox', [
            'requests' => $rows,
        ]);
    }

    public function approve(Request $request, LeaveRequestRoute $route): RedirectResponse
    {
        $this->authorizeAction($route);
        $validated = $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);

        $this->workflow->approve($route, $validated['comment'] ?? null);

        return redirect()->route('leave.requests.inbox')->with('success', 'อนุมัติใบลาเรียบร้อยแล้ว');
    }

    public function reject(Request $request, LeaveRequestRoute $route): RedirectResponse
    {
        $this->authorizeAction($route);
        $validated = $request->validate(['comment' => ['required', 'string', 'max:1000']]);

        $this->workflow->reject($route, $validated['comment']);

        return redirect()->route('leave.requests.inbox')->with('success', 'ตีกลับใบลาเรียบร้อยแล้ว');
    }

    /**
     * ยกเลิก/ถอนใบลา (เฉพาะเจ้าของ) — คืนวันลาถ้าอนุมัติไปแล้ว
     */
    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->user_id === $request->user()->id, 403);

        // ยกเลิกได้: ร่าง / รออนุมัติ / อนุมัติแล้ว (ตรงกับ can_cancel)
        abort_unless(
            in_array($leaveRequest->status, [LeaveRequest::STATUS_DRAFT, LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED], true),
            400,
        );

        // คืนวันลาถ้าตัดไปแล้ว
        if ($leaveRequest->status === LeaveRequest::STATUS_APPROVED) {
            $leaveRequest->restoreBalance();
        }

        // ปิดขั้นที่ยังค้างในเส้นทางอนุมัติ (ออกจาก inbox ผู้อนุมัติ)
        $leaveRequest->routes()
            ->where('status', LeaveRequestRoute::STATUS_PENDING)
            ->update(['status' => LeaveRequest::STATUS_CANCELLED]);

        $leaveRequest->update(['status' => LeaveRequest::STATUS_CANCELLED]);

        return back()->with('success', 'ยกเลิกใบลาเรียบร้อยแล้ว');
    }

    private function authorizeView(LeaveRequest $leave): void
    {
        $user = Auth::user();
        $allowed = $leave->user_id === $user->id
            || $user->hasRole('admin')
            || $leave->routes()->where('approver_id', $user->id)->exists();

        abort_unless($allowed, 403);
    }

    private function authorizeAction(LeaveRequestRoute $route): void
    {
        abort_unless(
            $route->approver_id === Auth::id() && $route->status === LeaveRequestRoute::STATUS_PENDING,
            403,
        );
    }

    /**
     * ช่วงปีงบประมาณไทย (1 ต.ค. – 30 ก.ย.) + ปี พ.ศ.
     *
     * @return array{0: string, 1: string, 2: int}
     */
    private function fiscalYear(?Carbon $ref = null): array
    {
        $ref = $ref ? $ref->copy() : now();
        $endYear = $ref->month >= 10 ? $ref->year + 1 : $ref->year;

        return [
            ($endYear - 1).'-10-01',
            $endYear.'-09-30',
            $endYear + 543,
        ];
    }

    /** วันที่ไทย เช่น ๑๖ มิถุนายน ๒๕๖๙ */
    private function thaiDate(Carbon $date): string
    {
        return $this->thaiNum($date->locale('th')->translatedFormat('j F').' '.($date->year + 543));
    }

    /** วันที่+เวลาไทย เช่น 16 มิ.ย. 2569 : 11:24 */
    private function thaiDateTime(Carbon $date): string
    {
        return $date->locale('th')->translatedFormat('j M').' '.($date->year + 543).' : '.$date->format('H:i');
    }

    /** แปลงเลขอารบิกเป็นเลขไทย */
    private function thaiNum(string $value): string
    {
        return strtr($value, ['0' => '๐', '1' => '๑', '2' => '๒', '3' => '๓', '4' => '๔', '5' => '๕', '6' => '๖', '7' => '๗', '8' => '๘', '9' => '๙']);
    }

    /** ชื่อตำแหน่งจาก role */
    private function roleLabel(User $user): string
    {
        $map = [
            'director' => 'ผู้อำนวยการ',
            'deputy_director' => 'รองผู้อำนวยการ',
            'secretary' => 'เลขานุการ',
            'leave_officer' => 'เจ้าหน้าที่งานวันลา',
            'teacher' => 'ครู',
        ];
        foreach ($user->getRoleNames() as $r) {
            if (isset($map[$r])) {
                return $map[$r];
            }
        }

        return 'บุคลากร';
    }

    /** แยก "[คำสั่งการ] ความเห็น" ออกจากกัน */
    private function parseComment(?string $comment): array
    {
        if (! $comment) {
            return ['actions' => [], 'note' => null];
        }
        if (preg_match('/^\[(.*?)\]\s*(.*)$/su', $comment, $m)) {
            return [
                'actions' => array_values(array_filter(array_map('trim', explode(',', $m[1])))),
                'note' => $m[2] !== '' ? $m[2] : null,
            ];
        }

        return ['actions' => [], 'note' => $comment];
    }
}
