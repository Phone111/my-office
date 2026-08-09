<?php

namespace Modules\Core\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\Approvable;
use Modules\Core\Contracts\ApprovalStep;
use Modules\Core\Notifications\ApprovalNotification;
use RuntimeException;

/**
 * เครื่องยนต์เส้นทางอนุมัติแบบใช้ซ้ำได้ (generic)
 * ใช้ร่วมกันทั้งโมดูล Saraban (เอกสาร) และ Leave (ใบลา)
 *
 * รับโมเดลที่ implement Approvable + รายชื่อ role ตามลำดับขั้น
 * แล้วจัดการสร้าง route, เลื่อนขั้น และแจ้งเตือนผู้เกี่ยวข้องอัตโนมัติ
 */
class ApprovalWorkflowService
{
    public const STEP_WAITING = 'waiting';
    public const STEP_PENDING = 'pending';
    public const STEP_APPROVED = 'approved';
    public const STEP_REJECTED = 'rejected';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * เริ่มเส้นทางอนุมัติ: สร้าง route ตามรายชื่อ role และเปิดขั้นแรก
     *
     * @param  array<int, string>  $roleNames  ชื่อ role ของผู้อนุมัติเรียงตามลำดับขั้น
     *
     * @throws RuntimeException เมื่อผู้ยื่นไม่มีกลุ่มงาน หรือหาผู้อนุมัติไม่ได้
     */
    public function start(Approvable $approvable, array $roleNames): void
    {
        $creator = $approvable->approvalCreator();

        if (! $creator) {
            throw new RuntimeException('ไม่สามารถส่งได้: ไม่พบผู้ยื่น');
        }

        // ยังไม่ได้กำหนดเส้นทาง (เช่น ผู้ยื่นไม่มีกลุ่มงาน) → ส่งตรงถึงผู้อำนวยการเป็นค่าเริ่มต้น
        if (empty($roleNames)) {
            $roleNames = ['director'];
        }

        DB::transaction(function () use ($approvable, $creator, $roleNames) {
            // ล้างเส้นทางเดิม (กรณีส่งใหม่หลังถูกตีกลับ)
            $approvable->approvalRoutes()->delete();

            $firstApprover = null;

            foreach (array_values($roleNames) as $index => $role) {
                $approver = $this->resolveApprover($role, $creator->department_id);

                if (! $approver) {
                    throw new RuntimeException("ไม่พบผู้อนุมัติที่มีสิทธิ์ \"{$role}\" สำหรับขั้นที่ ".($index + 1));
                }

                $approvable->approvalRoutes()->create([
                    'step_order' => $index + 1,
                    'approver_id' => $approver->id,
                    'status' => $index === 0 ? self::STEP_PENDING : self::STEP_WAITING,
                ]);

                if ($index === 0) {
                    $firstApprover = $approver;
                }
            }

            $approvable->setApprovalStatus(self::STATUS_PENDING);

            // แจ้งเตือนผู้อนุมัติขั้นแรก
            $this->notify(
                $firstApprover,
                'มีรายการรออนุมัติ',
                'มีรายการ "'.$approvable->approvalSubject().'" รอการพิจารณาจากคุณ',
                $approvable->approvalLink(),
            );
        });
    }

    /**
     * อนุมัติขั้นปัจจุบัน แล้วเปิดขั้นถัดไป (หรือปิดงานเมื่อครบทุกขั้น)
     */
    public function approve(ApprovalStep $route, ?string $comment = null): void
    {
        DB::transaction(function () use ($route, $comment) {
            $route->update([
                'status' => self::STEP_APPROVED,
                'comment' => $comment,
                'acted_at' => Carbon::now(),
            ]);

            /** @var Approvable $approvable */
            $approvable = $route->approvable;

            $next = $approvable->approvalRoutes()
                ->where('step_order', '>', $route->step_order)
                ->orderBy('step_order')
                ->first();

            if ($next) {
                $next->update(['status' => self::STEP_PENDING]);
                $this->notify(
                    $next->approver,
                    'มีรายการรออนุมัติ',
                    'มีรายการ "'.$approvable->approvalSubject().'" รอการพิจารณาจากคุณ',
                    $approvable->approvalLink(),
                );
            } else {
                $approvable->setApprovalStatus(self::STATUS_APPROVED);
                $this->notify(
                    $approvable->approvalCreator(),
                    'รายการได้รับอนุมัติแล้ว',
                    'รายการ "'.$approvable->approvalSubject().'" ผ่านการอนุมัติครบทุกขั้นแล้ว',
                    $approvable->approvalLink(),
                    'success',
                );
            }
        });
    }

    /**
     * ตีกลับ — จบเส้นทางทันทีและแจ้งผู้ยื่น
     */
    public function reject(ApprovalStep $route, ?string $comment = null): void
    {
        DB::transaction(function () use ($route, $comment) {
            $route->update([
                'status' => self::STEP_REJECTED,
                'comment' => $comment,
                'acted_at' => Carbon::now(),
            ]);

            /** @var Approvable $approvable */
            $approvable = $route->approvable;
            $approvable->setApprovalStatus(self::STATUS_REJECTED);

            $this->notify(
                $approvable->approvalCreator(),
                'รายการถูกตีกลับ',
                'รายการ "'.$approvable->approvalSubject().'" ถูกตีกลับ'.($comment ? ': '.$comment : ''),
                $approvable->approvalLink(),
                'danger',
            );
        });
    }

    /**
     * หา user ที่ถือ role — เลือกคนในกลุ่มงานเดียวกันก่อน ถ้าไม่มีจึงใช้คนอื่น
     */
    private function resolveApprover(string $roleName, ?int $departmentId): ?User
    {
        // ยุบ role 5 ระดับ — แปลงชื่อ role เดิมเป็นใหม่ก่อนค้นหา
        $base = User::whereHas('roles', fn ($q) => $q->where('name', User::newRoleFor($roleName)));

        if ($departmentId) {
            $sameDept = (clone $base)->where('department_id', $departmentId)->first();
            if ($sameDept) {
                return $sameDept;
            }
        }

        return $base->first();
    }

    /**
     * ส่ง notification (ข้ามถ้าไม่มีผู้รับ)
     */
    private function notify(?User $user, string $title, string $message, string $url, string $type = 'info'): void
    {
        $user?->notify(new ApprovalNotification($title, $message, $url, $type));
    }
}
