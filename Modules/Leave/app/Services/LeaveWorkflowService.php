<?php

namespace Modules\Leave\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\ApprovalFlow;
use Modules\Core\Notifications\ApprovalNotification;
use Modules\Core\Services\ApprovalWorkflowService;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Models\LeaveRequestRoute;

/**
 * เส้นทางอนุมัติใบลา:
 *   ผู้ยื่น → เจ้าหน้าที่งานวันลา (ตรวจ/เสนอผู้อนุญาต) → ผู้อนุญาต (ผอ./รองผอ.) → อนุมัติ
 * ถ้าไม่มีเจ้าหน้าที่วันลา จะใช้เส้นทางตาม approval_flows เดิม
 */
class LeaveWorkflowService
{
    public function __construct(private readonly ApprovalWorkflowService $engine)
    {
    }

    public function submit(LeaveRequest $request): LeaveRequest
    {
        $creator = $request->user;

        $officer = User::whereHas('roles', fn ($q) => $q->where('name', 'leave_officer'))
            ->when($creator?->unit_id, fn ($q) => $q->where('unit_id', $creator->unit_id))
            ->where('id', '!=', $creator?->id)
            ->first();

        // ด่านแรก = เจ้าหน้าที่งานวันลา
        if ($officer) {
            DB::transaction(function () use ($request, $officer) {
                $request->routes()->delete();
                $request->routes()->create([
                    'step_order' => 1,
                    'approver_id' => $officer->id,
                    'status' => LeaveRequestRoute::STATUS_PENDING,
                ]);
                $request->setApprovalStatus(LeaveRequest::STATUS_PENDING);
            });

            $officer->notify(new ApprovalNotification(
                'มีใบลารอดำเนินการ',
                'ใบลา "'.$request->approvalSubject().'" รอเจ้าหน้าที่งานวันลาดำเนินการ',
                route('leave.requests.proposal', $request->id),
                'info',
                'leave:'.$request->id,
            ));

            return $request->fresh('routes');
        }

        // ไม่มีเจ้าหน้าที่วันลา → ใช้เส้นทางเดิม
        $roleSteps = ApprovalFlow::roleStepsFor($creator->department_id ?? 0);
        $this->engine->start($request, $roleSteps);

        return $request->fresh('routes');
    }

    /**
     * เจ้าหน้าที่วันลาเสนอต่อผู้อนุญาต — ปิดขั้นเจ้าหน้าที่ แล้วเปิดขั้นผู้อนุญาต
     */
    public function forwardToApprover(LeaveRequest $request, LeaveRequestRoute $officerRoute, User $approver, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $officerRoute, $approver, $note) {
            $officerRoute->update([
                'status' => LeaveRequestRoute::STATUS_APPROVED,
                'comment' => $note ?: 'เสนอผู้อนุญาตการลา',
                'acted_at' => Carbon::now(),
            ]);

            $request->routes()->create([
                'step_order' => $officerRoute->step_order + 1,
                'approver_id' => $approver->id,
                'status' => LeaveRequestRoute::STATUS_PENDING,
            ]);
        });

        $approver->notify(new ApprovalNotification(
            'มีใบลารออนุมัติ',
            'ใบลา "'.$request->approvalSubject().'" รอการพิจารณาจากคุณ',
            route('leave.requests.proposal', $request->id),
            'info',
            'leave:'.$request->id,
        ));
    }

    public function approve(LeaveRequestRoute $route, ?string $comment = null): void
    {
        $this->engine->approve($route, $comment);
    }

    public function reject(LeaveRequestRoute $route, ?string $comment = null): void
    {
        $this->engine->reject($route, $comment);
    }
}
