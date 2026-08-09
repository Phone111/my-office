<?php

namespace Modules\Saraban\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\ApprovalFlow;
use Modules\Core\Notifications\ApprovalNotification;
use Modules\Core\Services\ApprovalWorkflowService;
use Modules\Saraban\Models\Document;
use Modules\Saraban\Models\DocumentRoute;

/**
 * ตัวห่อบาง ๆ ที่นำ ApprovalWorkflowService ของ Core มาใช้กับเอกสารสารบรรณ
 * (อ่านเส้นทางจาก approval_flows ตามกลุ่มงานของผู้สร้าง)
 */
class DocumentWorkflowService
{
    public function __construct(private readonly ApprovalWorkflowService $engine)
    {
    }

    /**
     * ส่งเอกสารเข้าสู่เส้นทางอนุมัติอัตโนมัติ (ตามผังเดิม — สำรองไว้)
     */
    public function submit(Document $document): Document
    {
        $roleSteps = ApprovalFlow::roleStepsFor($document->creator->department_id ?? 0);

        $this->engine->start($document, $roleSteps);

        return $document->fresh('routes');
    }

    /**
     * เริ่มเส้นทางแบบเลือกผู้เสนอเอง — ตั้งขั้นแรกถึงผู้ที่ผู้เขียนเลือก
     * (เจ้าหน้าที่เสนอหัวหน้ากลุ่ม → หัวหน้ากลุ่มเสนอรองผอ. → รองผอ.เสนอผอ.)
     */
    public function startTo(Document $document, int $approverId): void
    {
        DB::transaction(function () use ($document, $approverId) {
            // ล้างเส้นทางเดิม (กรณีส่งใหม่หลังถูกตีกลับ)
            $document->approvalRoutes()->delete();

            $document->approvalRoutes()->create([
                'step_order' => 1,
                'approver_id' => $approverId,
                'status' => DocumentRoute::STATUS_PENDING,
            ]);

            $document->setApprovalStatus(ApprovalWorkflowService::STATUS_PENDING);

            $this->notifyApprover($approverId, $document);
        });
    }

    /**
     * เสนอต่อ — อนุมัติขั้นปัจจุบัน แล้วเปิดขั้นถัดไปถึงผู้ที่เลือก
     */
    public function forward(DocumentRoute $route, int $approverId, ?string $comment = null): void
    {
        DB::transaction(function () use ($route, $approverId, $comment) {
            $route->update([
                'status' => DocumentRoute::STATUS_APPROVED,
                'comment' => $comment,
                'acted_at' => Carbon::now(),
            ]);

            $document = $route->document;

            $document->routes()->create([
                'step_order' => $route->step_order + 1,
                'approver_id' => $approverId,
                'status' => DocumentRoute::STATUS_PENDING,
            ]);

            $this->notifyApprover($approverId, $document);
        });
    }

    /**
     * แจ้งเตือนผู้อนุมัติที่ถึงคิว
     */
    private function notifyApprover(int $approverId, Document $document): void
    {
        User::find($approverId)?->notify(new ApprovalNotification(
            'มีรายการรออนุมัติ',
            'มีรายการ "'.$document->title.'" รอการพิจารณาจากคุณ',
            $document->approvalLink(),
            'info',
            'document:'.$document->id,
        ));
    }

    public function approve(DocumentRoute $route, ?string $comment = null): void
    {
        $this->engine->approve($route, $comment);
    }

    public function reject(DocumentRoute $route, ?string $comment = null): void
    {
        $this->engine->reject($route, $comment);
    }
}
